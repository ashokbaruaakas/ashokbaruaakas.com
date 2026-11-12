<?php

declare(strict_types=1);

namespace App\Ai;

use App\Actions\Portfolio\ResolveResumeAction;
use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Tools\Request;

/**
 * The resume changes the assistant can propose, in the shape a proposal stores
 * them: the settings, the ids each list includes in order, and the rewording.
 *
 * @phpstan-type Changes array{name?: string, template?: string, accent?: string|null, font?: string|null, job_application_id?: int|null, experience?: list<int>, projects?: list<int>, education?: list<int>, skills?: list<int>, headline?: string|null, summary?: string|null, highlights?: array<int|string, list<string>|null>}
 */
final readonly class ResumeChange
{
    public const string SECTION = 'resume';

    public const string NEW_RESUME_SECTION = 'new_resume';

    private const array LABELS = [
        'name' => 'Name',
        'template' => 'Template',
        'accent' => 'Colour',
        'font' => 'Font',
        'job_application_id' => 'Tailored For',
        'experience' => 'Experience',
        'projects' => 'Projects',
        'education' => 'Education',
        'skills' => 'Skills',
        'headline' => 'Headline',
        'summary' => 'Professional Summary',
        'highlights' => 'Highlights',
    ];

    public function __construct(private ResolveResumeAction $resolveResume) {}

    public static function label(string $field): string
    {
        return self::LABELS[$field] ?? $field;
    }

    /**
     * Read the resume changes a tool call asks for, as a proposal stores them.
     * A new resume starts on the classic template.
     *
     * @return Changes
     */
    public static function fromToolRequest(Request $request, bool $isNew = false): array
    {
        $changes = [];

        foreach (['name', 'template', 'accent', 'font'] as $field) {
            if ($request->filled($field)) {
                $changes[$field] = mb_trim($request->string($field)->toString());
            }
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
            $changes += ['template' => ResumeTemplate::Classic->value];
        }

        /** @var Changes $changes */
        return $changes;
    }

    /**
     * The tool parameters for the design, the lists and the rewording of a resume.
     *
     * @return array<string, Type>
     */
    public static function toolSchema(JsonSchema $schema): array
    {
        $ids = fn (string $what) => $schema->array()->items($schema->integer())->description("The ids of the {$what} to include, in order.");

        return [
            'template' => $schema->string()->enum(ResumeTemplate::values())->description('classic: one column, fits most resumes on two pages. compact: the same column, denser, aims at one page. sidebar: two columns with contact, summary, skills and languages on the left; fits a lot on one page, but a few older applicant tracking systems mix the columns, so prefer classic or compact when the job goes through one.'),
            'accent' => $schema->string()->enum(ResumeAccent::values())->description('The colour of the heading rules and bars. Leave it out to keep the current one; Emerald by default.'),
            'font' => $schema->string()->enum(ResumeFont::values())->description('arial: plain and safe everywhere. poppins: modern, with the name in Playfair Display. instrument: clean and narrow. Leave it out to keep the current one; the template has its own default.'),
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
     * Show a stored value on a proposal card.
     *
     * @return string|list<string>|null
     */
    public static function display(string $field, mixed $value): string|array|null
    {
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'template' => ResumeTemplate::tryFrom((string) $value)?->label(),
            'accent' => is_string($value) ? ResumeAccent::tryFrom($value)?->label() : null,
            'font' => is_string($value) ? ResumeFont::tryFrom($value)?->label() : null,
            'job_application_id' => ($job = JobApplication::query()->find($value)) instanceof JobApplication ? "{$job->company} — {$job->role}" : null,
            'experience', 'projects', 'education', 'skills' => self::itemLabels(ResumeList::from($field), is_array($value) ? $value : []),
            'highlights' => self::highlightLines(is_array($value) ? $value : []),
            default => is_scalar($value) ? (string) $value : null,
        };
    }

    /**
     * A resume as its editor shows it, as a plain outline: the design, every
     * portfolio item of each list in order, marked [x] when it is included, and
     * the rewording.
     */
    public function outline(Resume $resume): string
    {
        $design = $resume->design();
        $resolved = $this->resolveResume->handle($resume);
        $job = $resume->job_application_id === null ? null : JobApplication::query()->find($resume->job_application_id);
        $lines = [
            "Template: {$design['template']}. Colour: {$design['accent']}. Font: {$design['font']}.",
            'Tailored for: ' . ($job instanceof JobApplication ? "{$job->company} — {$job->role} (job id {$job->id})" : 'no particular job') . '.',
        ];

        foreach (ResumeList::cases() as $list) {
            $included = array_values(array_filter(array_map(fn (object $item): ?int => $item->id, $resolved->{$list->value})));
            $labels = self::labelsById($list, array_map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $list->items()->pluck('id')->all()));
            $order = [...$included, ...array_diff(array_keys($labels), $included)];

            $lines[] = '';
            $lines[] = self::label($list->value) . ' (id, in order):';

            foreach ($order as $id) {
                $lines[] = '  [' . (in_array($id, $included, true) ? 'x' : ' ') . "] {$id} " . ($labels[$id] ?? '');
            }
        }

        $lines[] = '';
        $lines[] = 'Headline: ' . ($resume->textOverride('headline') ?? 'the portfolio headline');
        $lines[] = 'Professional summary: ' . ($resume->textOverride('summary') ?? 'the portfolio summary');

        foreach ($resume->overrides['highlights'] ?? [] as $experienceId => $override) {
            $lines[] = "Highlights reworded for experience {$experienceId}: " . implode(' / ', $override['items']);
        }

        return implode("\n", $lines);
    }

    /**
     * The current values of the fields a change touches, in the same shape.
     *
     * @param  Changes  $changes
     * @return Changes
     */
    public function current(Resume $resume, array $changes): array
    {
        $resolved = $this->resolveResume->handle($resume);
        $current = [];

        foreach (array_keys($changes) as $field) {
            $current[$field] = match ($field) {
                'name' => $resume->name,
                'template' => $resume->template->value,
                'accent' => $resume->accent?->value,
                'font' => $resume->font?->value,
                'job_application_id' => $resume->job_application_id,
                'experience', 'projects', 'education', 'skills' => array_values(array_filter(array_map(fn (object $item): ?int => $item->id, $resolved->{$field}))),
                'headline', 'summary' => $resume->textOverride($field),
                'highlights' => array_map(fn (int|string $experienceId): ?array => $resume->highlightsOverride((int) $experienceId), array_combine(array_keys($changes['highlights'] ?? []), array_keys($changes['highlights'] ?? []))),
                default => null,
            };
        }

        return $current;
    }

    /**
     * Turn a change into the data the resume editor saves, keeping everything the change leaves alone.
     *
     * @param  Changes  $changes
     * @return array{name: string, template: string, accent: string|null, font: string|null, job_application_id: int|null, selection: array<string, list<array{id: int, included: bool}>>, overrides: array{headline: string|null, summary: string|null, highlights: array<int|string, list<string>|null>}}
     */
    public function toEditorData(Resume $resume, array $changes): array
    {
        $selection = $resume->selection ?? [];

        foreach (ResumeList::cases() as $list) {
            if (! array_key_exists($list->value, $changes)) {
                continue;
            }

            $included = array_values(array_map(intval(...), $changes[$list->value] ?? []));
            $others = $list->items()->whereKeyNot($included)->pluck('id')->map(intval(...))->all();
            $selection[$list->value] = [
                ...array_map(fn (int $id): array => ['id' => $id, 'included' => true], $included),
                ...array_map(fn (int $id): array => ['id' => $id, 'included' => false], $others),
            ];
        }

        $highlights = [];

        foreach ($resume->overrides['highlights'] ?? [] as $experienceId => $override) {
            $highlights[$experienceId] = $override['items'];
        }

        foreach ($changes['highlights'] ?? [] as $experienceId => $items) {
            $highlights[$experienceId] = $items;
        }

        return [
            'name' => $changes['name'] ?? $resume->name,
            'template' => $changes['template'] ?? $resume->template->value,
            'accent' => array_key_exists('accent', $changes) ? $changes['accent'] : $resume->accent?->value,
            'font' => array_key_exists('font', $changes) ? $changes['font'] : $resume->font?->value,
            'job_application_id' => array_key_exists('job_application_id', $changes) ? $changes['job_application_id'] : $resume->job_application_id,
            'selection' => $selection,
            'overrides' => [
                'headline' => array_key_exists('headline', $changes) ? $changes['headline'] : $resume->textOverride('headline'),
                'summary' => array_key_exists('summary', $changes) ? $changes['summary'] : $resume->textOverride('summary'),
                'highlights' => $highlights,
            ],
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @return list<string>
     */
    private static function itemLabels(ResumeList $list, array $ids): array
    {
        $labels = self::labelsById($list, $ids);

        return array_values(array_filter(array_map(fn (int $id): ?string => $labels[$id] ?? null, array_map(intval(...), $ids))));
    }

    /**
     * @param  array<int>  $ids
     * @return array<int, string>
     */
    private static function labelsById(ResumeList $list, array $ids): array
    {
        return $list->portfolioList()->model()::query()->whereKey($ids)->get()
            ->mapWithKeys(fn ($item): array => [(int) $item->getKey() => match ($list) {
                ResumeList::Experience => "{$item->role} — {$item->company}",
                ResumeList::Projects => $item->name,
                ResumeList::Education => "{$item->degree} — {$item->school}",
                default => $item->name,
            }])->all();
    }

    /**
     * @param  array<int|string, list<string>|null>  $highlights
     * @return list<string>
     */
    private static function highlightLines(array $highlights): array
    {
        $experiences = Experience::query()->whereKey(array_keys($highlights))->get()->keyBy('id');
        $lines = [];

        foreach ($highlights as $experienceId => $items) {
            $experience = $experiences->get((int) $experienceId);
            $role = $experience instanceof Experience ? "{$experience->role} — {$experience->company}" : 'A role';

            $lines[] = $items === null ? "{$role}: the portfolio highlights" : "{$role}: " . implode(' / ', $items);
        }

        return $lines;
    }
}
