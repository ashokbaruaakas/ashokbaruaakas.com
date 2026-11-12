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
        $items = $list->portfolioList()->model()::query()->whereKey($ids)->get()->keyBy(fn ($item): int => (int) $item->getKey());

        return array_values(array_filter(array_map(fn (int $id): ?string => match (true) {
            ! $items->has($id) => null,
            $list === ResumeList::Experience => "{$items->get($id)->role} — {$items->get($id)->company}",
            $list === ResumeList::Projects => $items->get($id)->name,
            $list === ResumeList::Education => "{$items->get($id)->degree} — {$items->get($id)->school}",
            default => $items->get($id)->name,
        }, array_map(intval(...), $ids))));
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
