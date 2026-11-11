<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\AssistantTurn;
use App\Ai\ResumeChange;
use App\Enums\ProposalStatus;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\AssistantProposal;
use App\Models\Experience;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ProposeResume implements Tool
{
    public function __construct(
        private AssistantTurn $turn,
        private ResumeChange $resumeChange,
    ) {}

    public function description(): string
    {
        return 'Suggest a new resume, such as one tailored for a job, or a change to an existing one (give resume_id). '
            . 'A resume picks portfolio items and their order and can reword the headline, the summary and a role\'s highlights, '
            . 'without changing the portfolio itself. The owner sees a card and applies it with one click. '
            . 'Send only what changes. A list you send replaces that list: give every item id to include, in order; the rest are left out. '
            . 'Read ListResumes and ReadPortfolio first so the ids and the current page count are right. Rewording follows the writing rules.';
    }

    public function handle(Request $request): string
    {
        $resume = $request->filled('resume_id') ? Resume::query()->active()->find($request->integer('resume_id')) : null;

        if ($request->filled('resume_id') && ! $resume instanceof Resume) {
            return 'There is no resume with that id. Call ListResumes for the ids.';
        }

        $changes = $this->changes($request, $resume === null);
        $validator = Validator::make($changes, $this->rules($resume === null));

        if ($validator->fails()) {
            return 'The resume was not proposed: ' . implode(' ', $validator->errors()->all());
        }

        $unknownRoles = array_diff(array_keys($changes['highlights'] ?? []), Experience::query()->pluck('id')->all());

        if ($unknownRoles !== []) {
            return 'The resume was not proposed: there is no experience entry with id ' . implode(', ', $unknownRoles) . '. Call ReadPortfolio for the ids.';
        }

        if ($resume instanceof Resume) {
            $current = $this->resumeChange->current($resume, $changes);
            $changes = array_filter($changes, fn (mixed $value, string $field): bool => ! AssistantProposal::sameValue($value, $current[$field] ?? null), ARRAY_FILTER_USE_BOTH);

            if ($changes === []) {
                return 'That is how the resume already is, so there is nothing to propose.';
            }
        }

        $proposal = AssistantProposal::query()->create([
            'section' => $resume === null ? ResumeChange::NEW_RESUME_SECTION : ResumeChange::SECTION,
            'item_id' => $resume?->id,
            'summary' => $request->string('summary')->limit(250)->toString(),
            'changes' => $changes,
            'before' => $resume === null ? null : array_intersect_key($this->resumeChange->current($resume, $changes), $changes),
            'status' => ProposalStatus::Pending,
        ]);

        $this->turn->created($proposal);

        return 'The owner now sees this as a card under your reply and can apply it with one click; its PDF is then built and its page count checked. Do not repeat the details in your reply.';
    }

    public function schema(JsonSchema $schema): array
    {
        $ids = fn (string $what) => $schema->array()->items($schema->integer())->description("The ids of the {$what} to include, in order.");

        return [
            'resume_id' => $schema->integer()->description('The resume to change. Leave it out to propose a new resume.'),
            'summary' => $schema->string()->description('One short line, such as "A one-page resume for Acme — Backend Engineer".')->required(),
            'name' => $schema->string()->description('The resume\'s name. Required for a new resume.'),
            'job_id' => $schema->integer()->description('The job the resume is tailored for.'),
            'template' => $schema->string()->enum(ResumeTemplate::values())->description('classic fits most resumes on two pages; compact is denser and aims at one page.'),
            'page_limit' => $schema->integer()->description('The most pages the PDF may have, 1 to 5.'),
            ResumeList::Experience->value => $ids('experience entries'),
            ResumeList::Projects->value => $ids('projects'),
            ResumeList::Education->value => $ids('education entries'),
            ResumeList::Skills->value => $ids('skill categories'),
            'headline' => $schema->string()->description('Replaces the headline (the line under the name, written as "Title — Focus"). Empty text goes back to the portfolio headline.'),
            'summary_text' => $schema->string()->description('Replaces the professional summary. Empty text goes back to the portfolio summary.'),
            'highlights' => $schema->array()->items($schema->object([
                'experience_id' => $schema->integer()->required(),
                'items' => $schema->array()->items($schema->string())->description('The highlights for this role, one per entry. Empty goes back to the portfolio highlights.')->required(),
            ]))->description('Replaces the highlights of the given roles.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(Request $request, bool $isNew): array
    {
        $changes = [];

        foreach (['name', 'template'] as $field) {
            if ($request->filled($field)) {
                $changes[$field] = mb_trim($request->string($field)->toString());
            }
        }

        if ($request->filled('page_limit')) {
            $changes['page_limit'] = $request->integer('page_limit');
        }

        if ($request->filled('job_id')) {
            $changes['job_application_id'] = $request->integer('job_id');
        }

        foreach (ResumeList::values() as $list) {
            if (is_array($request[$list] ?? null)) {
                $changes[$list] = array_values(array_unique(array_map(intval(...), array_filter($request[$list], is_numeric(...)))));
            }
        }

        foreach (['headline' => 'headline', 'summary_text' => 'summary'] as $input => $field) {
            if (is_string($request[$input] ?? null)) {
                $text = mb_trim($request[$input]);
                $changes[$field] = $text === '' ? null : $text;
            }
        }

        foreach (is_array($request['highlights'] ?? null) ? $request['highlights'] : [] as $role) {
            if (is_array($role) && is_numeric($role['experience_id'] ?? null)) {
                $items = array_values(array_filter(array_map(fn (mixed $item): string => is_scalar($item) ? mb_trim((string) $item) : '', is_array($role['items'] ?? null) ? $role['items'] : []), filled(...)));
                $changes['highlights'][(int) $role['experience_id']] = $items === [] ? null : $items;
            }
        }

        if ($isNew) {
            $changes += ['template' => ResumeTemplate::Classic->value, 'page_limit' => 2];
        }

        return $changes;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $isNew): array
    {
        $rules = [
            'name' => [$isNew ? 'required' : 'sometimes', 'string', 'max:100'],
            'template' => ['sometimes', Rule::enum(ResumeTemplate::class)],
            'page_limit' => ['sometimes', 'integer', 'between:1,5'],
            'job_application_id' => ['sometimes', 'nullable', Rule::exists('job_applications', 'id')],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'highlights' => ['sometimes', 'array'],
            'highlights.*' => ['nullable', 'array', 'max:20'],
            'highlights.*.*' => ['string', 'max:500'],
        ];

        foreach (ResumeList::cases() as $list) {
            $rules[$list->value] = ['sometimes', 'array'];
            $rules["{$list->value}.*"] = ['integer', Rule::exists($list->portfolioList()->model()::query()->getModel()->getTable(), 'id')];
        }

        return $rules;
    }
}
