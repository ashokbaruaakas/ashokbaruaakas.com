<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type TextOverride from Resume
 * @phpstan-import-type HighlightsOverride from Resume
 *
 * @phpstan-type ResumeData array{name: string, template: string, accent?: string|null, font?: string|null, job_application_id?: int|null, selection?: array<string, list<array{id: int, included: bool}>>, overrides?: array{headline?: string|null, summary?: string|null, highlights?: array<int|string, list<string>|null>}, reviewed_overrides?: list<string>}
 */
final readonly class SaveResumeAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Start a resume, copying another one's settings or including every portfolio
     * item, and build its PDF right away.
     */
    public function create(string $name, ?Resume $duplicateOf = null, ?int $jobApplicationId = null): Resume
    {
        $resume = $this->blank($name, $duplicateOf, $jobApplicationId);
        $resume->save();

        $this->scheduleRebuild->handle(immediately: true, resume: $resume);

        return $resume;
    }

    /**
     * A new resume as create() starts it, without saving it.
     */
    public function blank(string $name, ?Resume $duplicateOf = null, ?int $jobApplicationId = null): Resume
    {
        return new Resume([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'template' => $duplicateOf->template ?? ResumeTemplate::Classic,
            'accent' => $duplicateOf?->accent,
            'font' => $duplicateOf?->font,
            'is_public' => false,
            'job_application_id' => $jobApplicationId,
            'selection' => $duplicateOf->selection ?? $this->everyItem(),
            'overrides' => $duplicateOf?->overrides,
        ]);
    }

    /**
     * Save a resume's settings, selection and overrides, and rebuild its PDF:
     * right away for a new design or when asked, after the usual wait otherwise. An override
     * remembers the portfolio text it replaced when it is written or reviewed.
     *
     * @param  ResumeData  $data
     */
    public function update(Resume $resume, array $data, bool $rebuildNow = false): void
    {
        $this->fill($resume, $data);

        $isNewDesign = $resume->isDirty(['template', 'accent', 'font']);

        $resume->save();

        $this->scheduleRebuild->handle(immediately: $isNewDesign || $rebuildNow, resume: $resume);
    }

    /**
     * The resume as it would be with these changes, without saving it, for previews.
     *
     * @param  ResumeData  $data
     */
    public function draft(Resume $resume, array $data): Resume
    {
        $draft = $resume->replicate();

        $this->fill($draft, $data, $resume);

        return $draft;
    }

    /**
     * @param  ResumeData  $data
     */
    private function fill(Resume $resume, array $data, ?Resume $saved = null): void
    {
        $resume->fill([
            'name' => $data['name'],
            'template' => $data['template'],
            'accent' => $data['accent'] ?? null,
            'font' => $data['font'] ?? null,
            'job_application_id' => $data['job_application_id'] ?? null,
        ]);

        if (array_key_exists('selection', $data)) {
            $resume->selection = array_map(fn (array $entries): array => array_map(fn (array $entry): array => ['id' => (int) $entry['id'], 'included' => (bool) $entry['included']], $entries), $data['selection']);
        }

        if (array_key_exists('overrides', $data)) {
            $resume->overrides = $this->overrides($saved ?? $resume, $data['overrides'], $data['reviewed_overrides'] ?? []);
        }
    }

    /**
     * @param  array{headline?: string|null, summary?: string|null, highlights?: array<int|string, list<string>|null>}  $submitted
     * @param  list<string>  $reviewed
     * @return array{headline?: TextOverride, summary?: TextOverride, highlights?: array<int, HighlightsOverride>}
     */
    private function overrides(Resume $resume, array $submitted, array $reviewed): array
    {
        $profile = Profile::query()->first();
        $current = $resume->overrides ?? [];
        $overrides = [];

        $headline = $this->textOverride($current['headline'] ?? null, $submitted['headline'] ?? null, $profile->tagline ?? '', in_array('headline', $reviewed, true));
        $summary = $this->textOverride($current['summary'] ?? null, $submitted['summary'] ?? null, $profile->professional_summary ?? '', in_array('summary', $reviewed, true));

        if ($headline !== null) {
            $overrides['headline'] = $headline;
        }

        if ($summary !== null) {
            $overrides['summary'] = $summary;
        }

        $experiences = Experience::query()->whereIn('id', array_keys($submitted['highlights'] ?? []))->get()->keyBy('id');
        $highlights = [];

        foreach ($submitted['highlights'] ?? [] as $experienceId => $items) {
            $items = array_values(array_filter(array_map(fn (?string $item): string => mb_trim((string) $item), $items ?? []), filled(...)));
            $experience = $experiences->get((int) $experienceId);

            if ($items === [] || $experience === null) {
                continue;
            }

            $existing = $current['highlights'][$experienceId] ?? null;
            $keepsHash = $existing !== null && $existing['items'] === $items && ! in_array("highlights.{$experienceId}", $reviewed, true);
            $highlights[(int) $experienceId] = ['items' => $items, 'base_hash' => $keepsHash ? $existing['base_hash'] : Resume::baseHash(array_values($experience->highlights))];
        }

        if ($highlights !== []) {
            $overrides['highlights'] = $highlights;
        }

        return $overrides;
    }

    /**
     * An override of the headline or summary, remembering the portfolio text it replaced. Null when the text is empty.
     *
     * @param  TextOverride|null  $current
     * @return TextOverride|null
     */
    private function textOverride(?array $current, ?string $submitted, string $base, bool $isReviewed): ?array
    {
        $text = mb_trim($submitted ?? '');

        if ($text === '') {
            return null;
        }

        $keepsHash = $current !== null && $current['text'] === $text && ! $isReviewed;

        return ['text' => $text, 'base_hash' => $keepsHash ? $current['base_hash'] : Resume::baseHash($base)];
    }

    /**
     * @return array<string, list<array{id: int, included: bool}>>
     */
    private function everyItem(): array
    {
        $selection = [];

        foreach (ResumeList::cases() as $list) {
            $entries = [];

            foreach ($list->items()->get() as $item) {
                $id = $item->getKey();

                if (is_int($id)) {
                    $entries[] = ['id' => $id, 'included' => $list->includesByDefault($item instanceof Project ? $item->tier : null)];
                }
            }

            $selection[$list->value] = $entries;
        }

        return $selection;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'resume';
        $slug = $base;

        for ($suffix = 2; Resume::query()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
