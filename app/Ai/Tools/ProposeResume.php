<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Portfolio\MeasureResumeAction;
use App\Ai\AssistantTurn;
use App\Ai\ResumeChange;
use App\Ai\ResumeEditorContext;
use App\Enums\ProposalStatus;
use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\AssistantProposal;
use App\Models\Experience;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;

/**
 * @phpstan-import-type Changes from ResumeChange
 */
final readonly class ProposeResume implements Tool
{
    private const int MAX_JOB_KEYWORDS = 30;

    public function __construct(
        private AssistantTurn $turn,
        private ResumeChange $resumeChange,
        private ?ResumeEditorContext $editor = null,
        private ?MeasureResumeAction $measureResume = null,
    ) {}

    public function description(): string
    {
        return 'Suggest a new resume, such as one tailored for a job, or a change to an existing one (give resume_id). '
            . 'A resume picks portfolio items and their order and can reword the headline, the summary and a role\'s highlights, '
            . 'without changing the portfolio itself. The owner sees a card and applies it with one click. '
            . 'For a job, propose the whole resume in one card: the items and their order, rewording in the job\'s words, and a template, colour and font. '
            . 'Reword only what the portfolio already says: never add a number, tool, employer, title or result it does not have. '
            . 'For the resume the owner is editing, the card loads the change into the editor instead, where they check the preview and save it. '
            . 'The owner can preview the resume from the card before applying it, with its page estimate and, for a tailored resume, its ATS score against the job. '
            . 'When the owner asks for a length, such as one page, cut items and highlights or choose a denser template, and check it with MeasureResume until it fits. '
            . 'Send only what changes. A list you send replaces that list: give every item id to include, in order; the rest are left out. '
            . 'Read ListResumes and ReadPortfolio first so the ids and the current page count are right. Rewording follows the writing rules.';
    }

    public function handle(Request $request): string
    {
        $resume = $request->filled('resume_id') ? Resume::query()->active()->find($request->integer('resume_id')) : null;

        if ($request->filled('resume_id') && ! $resume instanceof Resume) {
            return 'There is no resume with that id. Call ListResumes for the ids.';
        }

        $changes = ResumeChange::fromToolRequest($request, $resume === null);
        $validator = Validator::make($changes, $this->rules($resume === null));

        if ($validator->fails()) {
            return 'The resume was not proposed: ' . implode(' ', $validator->errors()->all());
        }

        $unknownRoles = array_filter(array_keys($changes['highlights'] ?? []), fn (int|string $experienceId): bool => ! Experience::query()->whereKey($experienceId)->exists());

        if ($unknownRoles !== []) {
            return 'The resume was not proposed: there is no experience entry with id ' . implode(', ', $unknownRoles) . '. Call ReadPortfolio for the ids.';
        }

        $base = $resume instanceof Resume ? ResumeEditorContext::baseFor($resume, $this->editor) : null;

        if ($base instanceof Resume) {
            $current = $this->resumeChange->current($base, $changes);
            $changes = array_filter($changes, fn (mixed $value, string $field): bool => ! AssistantProposal::sameValue($value, $current[$field] ?? null), ARRAY_FILTER_USE_BOTH);

            if ($changes === []) {
                return 'That is how the resume already is, so there is nothing to propose.';
            }
        }

        $isEditing = $resume instanceof Resume && $this->editor?->isEditing($resume) === true;
        $fitsPages = $request->filled('fits_pages') ? max(1, $request->integer('fits_pages')) : null;
        $measured = $resume instanceof Resume && ($isEditing || $fitsPages !== null) ? $this->measure($resume, $base, $changes) : null;

        if ($fitsPages !== null && $measured !== null && $measured['pages'] > $fitsPages) {
            return 'The resume was not proposed: with these changes it ' . $this->length($measured) . ', more than ' . $fitsPages . ' ' . Str::plural('page', $fitsPages) . '. '
                . 'Cut more, check it with MeasureResume, and propose the version that fits.';
        }

        $proposal = AssistantProposal::query()->create([
            'section' => $resume === null ? ResumeChange::NEW_RESUME_SECTION : ResumeChange::SECTION,
            'item_id' => $resume?->id,
            'summary' => $request->string('summary')->limit(250)->toString(),
            'changes' => $changes,
            'before' => $base instanceof Resume ? array_intersect_key($this->resumeChange->current($base, $changes), $changes) : null,
            'job_keywords' => $this->jobKeywords($request),
            'status' => ProposalStatus::Pending,
        ]);

        $this->turn->created($proposal);

        $length = $measured === null ? '' : 'With it, the resume ' . $this->length($measured) . '; say that length if you mention one. ';

        if ($isEditing) {
            return 'The owner now sees this as a card under your reply and can load it into the editor with one click, then check the preview and save. '
                . $length . 'Do not repeat the details in your reply.';
        }

        return 'The owner now sees this as a card under your reply, can preview it, and can apply it with one click; its PDF is then built. '
            . $length . 'Do not repeat the details in your reply.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resume_id' => $schema->integer()->description('The resume to change. Leave it out to propose a new resume.'),
            'summary' => $schema->string()->description('One short line, such as "A one-page resume for Acme — Backend Engineer".')->required(),
            'name' => $schema->string()->description('The resume\'s name. Required for a new resume.'),
            'job_id' => $schema->integer()->description('The job the resume is tailored for.'),
            'fits_pages' => $schema->integer()->description('When the owner asked for a length, such as one page, the number of pages the resume must fit on. The resume is measured and the proposal is refused when it is longer.'),
            'job_keywords' => $schema->array()->items($schema->string())->description('For a tailored resume, the skills, tools and terms an applicant tracking system would screen the job post for, as written in the post. The preview scores the resume against them.'),
            ...ResumeChange::toolSchema($schema),
        ];
    }

    /**
     * @param  Changes  $changes
     * @return array{pages: int, lastPageFill: float}|null
     */
    private function measure(Resume $resume, Resume $base, array $changes): ?array
    {
        if (! $this->measureResume instanceof MeasureResumeAction) {
            return null;
        }

        try {
            return $this->measureResume->handle($resume, $this->resumeChange->toEditorData($base, $changes));
        } catch (RuntimeException $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array{pages: int, lastPageFill: float}  $measured
     */
    private function length(array $measured): string
    {
        return "lays out on {$measured['pages']} " . Str::plural('page', $measured['pages']) . ', the last one about ' . (int) round($measured['lastPageFill'] * 100) . '% full';
    }

    /**
     * @return list<string>|null
     */
    private function jobKeywords(Request $request): ?array
    {
        $keywords = array_values(array_unique(array_filter(array_map(
            fn (mixed $keyword): string => is_string($keyword) ? Str::limit(mb_trim($keyword), 60, '') : '',
            is_array($request['job_keywords'] ?? null) ? $request['job_keywords'] : [],
        ), filled(...))));

        return $keywords === [] ? null : array_slice($keywords, 0, self::MAX_JOB_KEYWORDS);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $isNew): array
    {
        $rules = [
            'name' => [$isNew ? 'required' : 'sometimes', 'string', 'max:100'],
            'template' => ['sometimes', Rule::enum(ResumeTemplate::class)],
            'accent' => ['sometimes', Rule::enum(ResumeAccent::class)],
            'font' => ['sometimes', Rule::enum(ResumeFont::class)],
            'job_application_id' => ['sometimes', 'nullable', Rule::exists('job_applications', 'id')],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'highlights' => ['sometimes', 'array'],
            'highlights.*' => ['nullable', 'array', 'max:20'],
            'highlights.*.*' => ['string', 'max:500'],
        ];

        foreach (ResumeList::cases() as $list) {
            $rules[$list->value] = ['sometimes', 'array'];
            $rules["{$list->value}.*"] = ['integer', Rule::exists($list->table(), 'id')];
        }

        return $rules;
    }
}
