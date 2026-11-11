<?php

declare(strict_types=1);

namespace App\Actions\Backup;

use App\Enums\ResumeList;
use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\Resume;
use App\Models\ResumeBuild;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Resumes and the builds sent with job applications, in and out of a backup.
 * Portfolio items and job applications get new ids when a backup is restored,
 * so a backup points at them by their position in its own lists.
 *
 * @phpstan-type JobPosition array{0: int, 1: int}
 */
final readonly class ResumeBackup
{
    public const string FILE_PREFIX = 'files/resume-builds/';

    /**
     * @return array{resumes: list<array<string, mixed>>, files: array<string, string>}
     *
     * @throws RuntimeException When a sent build's PDF is missing from storage.
     */
    public function export(): array
    {
        $positions = $this->itemPositions();
        $jobPositions = $this->jobPositions();
        $resumes = [];
        $files = [];

        foreach (Resume::query()->orderBy('id')->get() as $resume) {
            $sentBuilds = [];

            $builds = $resume->builds()->where('status', ResumePdfStatus::Ready)->whereHas('sentWith')->with('sentWith')->orderBy('id')->get();

            foreach ($builds as $build) {
                $contents = $build->path === null ? null : Storage::disk(ResumeBuild::DISK)->get($build->path);

                throw_unless(is_string($contents), RuntimeException::class, "The PDF sent with a job application from the {$resume->name} resume is missing from storage.");

                $file = self::FILE_PREFIX . count($files) . '.pdf';
                $files[$file] = $contents;
                $sentBuilds[] = [
                    'file' => $file,
                    'template' => $build->template?->value,
                    'page_count' => $build->page_count,
                    'snapshot' => $build->snapshot,
                    'built_at' => $build->built_at?->toIso8601String(),
                    'sent_with' => array_values(array_filter($build->sentWith->map(fn (JobApplication $application): ?array => $jobPositions[$application->id] ?? null)->all())),
                ];
            }

            $resumes[] = [
                'name' => $resume->name,
                'slug' => $resume->slug,
                'template' => $resume->template->value,
                'page_limit' => $resume->page_limit,
                'is_public' => $resume->is_public,
                'archived' => $resume->archived_at !== null,
                'tailored_for' => $resume->job_application_id === null ? null : ($jobPositions[$resume->job_application_id] ?? null),
                'selection' => $this->selectionToPositions($resume, $positions),
                'overrides' => $this->overridesToPositions($resume, $positions[ResumeList::Experience->value]),
                'sent_builds' => $sentBuilds,
            ];
        }

        return ['resumes' => $resumes, 'files' => $files];
    }

    /**
     * Backups made before resumes existed have no `resumes` key.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'resumes' => ['sometimes', 'array', 'max:50'],
            'resumes.*' => ['array'],
            'resumes.*.name' => ['required', 'string', 'max:100'],
            'resumes.*.slug' => ['required', 'string', 'max:100', 'alpha_dash', 'distinct'],
            'resumes.*.template' => ['required', Rule::enum(ResumeTemplate::class)],
            'resumes.*.page_limit' => ['required', 'integer', 'between:1,5'],
            'resumes.*.is_public' => ['required', 'boolean'],
            'resumes.*.archived' => ['required', 'boolean'],
            'resumes.*.tailored_for' => ['nullable', 'array', 'size:2'],
            'resumes.*.tailored_for.*' => ['integer', 'min:0'],
            'resumes.*.selection' => ['present', 'array'],
            'resumes.*.selection.*' => ['array'],
            'resumes.*.selection.*.*.position' => ['required', 'integer', 'min:0'],
            'resumes.*.selection.*.*.included' => ['required', 'boolean'],
            'resumes.*.overrides' => ['present', 'array'],
            'resumes.*.overrides.headline.text' => ['sometimes', 'string', 'max:255'],
            'resumes.*.overrides.summary.text' => ['sometimes', 'string', 'max:2000'],
            'resumes.*.overrides.*.base_hash' => ['sometimes', 'string', 'max:64'],
            'resumes.*.overrides.highlights' => ['sometimes', 'array'],
            'resumes.*.overrides.highlights.*.position' => ['required', 'integer', 'min:0'],
            'resumes.*.overrides.highlights.*.items' => ['required', 'array', 'max:20'],
            'resumes.*.overrides.highlights.*.items.*' => ['string', 'max:500'],
            'resumes.*.overrides.highlights.*.base_hash' => ['required', 'string', 'max:64'],
            'resumes.*.sent_builds' => ['present', 'array'],
            'resumes.*.sent_builds.*.file' => ['required', 'string', 'regex:#^' . preg_quote(self::FILE_PREFIX, '#') . '\d+\.pdf$#'],
            'resumes.*.sent_builds.*.template' => ['nullable', Rule::enum(ResumeTemplate::class)],
            'resumes.*.sent_builds.*.page_count' => ['nullable', 'integer', 'min:1'],
            'resumes.*.sent_builds.*.snapshot' => ['nullable', 'string'],
            'resumes.*.sent_builds.*.built_at' => ['nullable', 'date'],
            'resumes.*.sent_builds.*.sent_with' => ['present', 'array'],
            'resumes.*.sent_builds.*.sent_with.*' => ['array', 'size:2'],
            'resumes.*.sent_builds.*.sent_with.*.*' => ['integer', 'min:0'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $resumes
     * @return list<string>
     */
    public function files(?array $resumes): array
    {
        $files = [];

        foreach ($resumes ?? [] as $resume) {
            /** @var array<int, array{file: string}> $builds */
            $builds = $resume['sent_builds'] ?? [];

            foreach ($builds as $build) {
                $files[] = $build['file'];
            }
        }

        return $files;
    }

    /**
     * Replace every resume and its builds with the backup's. Backups from before
     * resumes existed get one public resume that keeps their projects' resume flags.
     *
     * @param  array<int, array<string, mixed>>|null  $resumes
     * @param  array<int, array<string, mixed>>  $backupProjects
     * @param  array<int, array<int, int>>|null  $applicationIds  New application ids by hunt and application position, or null when the hunts were left alone.
     * @param  array<string, string>  $files
     * @param  list<string>  $written  Collects each path written, for the caller to remove if the import fails.
     * @return list<string> The paths of the PDFs replaced, for the caller to remove once the import is saved.
     */
    public function restore(?array $resumes, array $backupProjects, ?array $applicationIds, array $files, array &$written): array
    {
        $previousPaths = ResumeBuild::query()->whereNotNull('path')->pluck('path')->all();
        $ids = $this->itemIdsByPosition();

        Resume::query()->delete();

        foreach ($resumes ?? [$this->legacyMainResume($backupProjects)] as $attributes) {
            $resume = Resume::query()->forceCreate([
                'name' => $attributes['name'],
                'slug' => $attributes['slug'],
                'template' => $attributes['template'],
                'page_limit' => $attributes['page_limit'],
                'is_public' => $attributes['is_public'],
                'published_at' => $attributes['is_public'] ? now() : null,
                'archived_at' => ($attributes['archived'] ?? false) ? now() : null,
                'job_application_id' => $this->applicationId($attributes['tailored_for'] ?? null, $applicationIds),
                'selection' => $this->selectionFromPositions($attributes['selection'] ?? [], $ids),
                'overrides' => $this->overridesFromPositions($attributes['overrides'] ?? [], $ids[ResumeList::Experience->value]),
            ]);

            /** @var array<int, array<string, mixed>> $sentBuilds */
            $sentBuilds = $attributes['sent_builds'] ?? [];

            foreach ($sentBuilds as $buildAttributes) {
                $token = (string) Str::ulid();
                $path = "resume-builds/{$resume->id}/{$token}.pdf";

                throw_unless(Storage::disk(ResumeBuild::DISK)->put($path, $files[$buildAttributes['file']]), RuntimeException::class, 'Unable to store the file.');

                $written[] = $path;
                $build = $resume->builds()->create([
                    'token' => $token,
                    'status' => ResumePdfStatus::Ready,
                    'scheduled_for' => now(),
                    'template' => $buildAttributes['template'] ?? null,
                    'page_count' => $buildAttributes['page_count'] ?? null,
                    'snapshot' => $buildAttributes['snapshot'] ?? null,
                    'path' => $path,
                    'built_at' => $buildAttributes['built_at'] ?? now(),
                ]);

                /** @var array<int, JobPosition> $sentWith */
                $sentWith = $buildAttributes['sent_with'] ?? [];

                foreach ($sentWith as $position) {
                    $applicationId = $this->applicationId($position, $applicationIds);

                    if ($applicationId !== null) {
                        JobApplication::query()->whereKey($applicationId)->update(['resume_build_id' => $build->id]);
                    }
                }
            }
        }

        if (! Resume::query()->where('is_public', true)->exists()) {
            Resume::query()->orderBy('id')->firstOrFail()->update(['is_public' => true, 'published_at' => now()]);
        }

        return array_values(array_diff($previousPaths, $written));
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<string, mixed>
     */
    private function legacyMainResume(array $projects): array
    {
        return [
            'name' => 'Main',
            'slug' => 'main',
            'template' => ResumeTemplate::Classic->value,
            'page_limit' => 2,
            'is_public' => true,
            'selection' => [ResumeList::Projects->value => array_map(fn (int $position, array $project): array => [
                'position' => $position,
                'included' => (bool) ($project['show_on_resume'] ?? true) && ResumeList::Projects->includesByDefault((int) ($project['tier'] ?? 0)),
            ], array_keys(array_values($projects)), array_values($projects))],
            'overrides' => [],
            'sent_builds' => [],
        ];
    }

    /**
     * @return array<string, array<int, int>> Positions by item id, per list.
     */
    private function itemPositions(): array
    {
        return array_map(array_flip(...), $this->itemIdsByPosition());
    }

    /**
     * @return array<string, array<int, int>> Item ids by position, per list, in the order backups list them.
     */
    private function itemIdsByPosition(): array
    {
        $ids = [];

        foreach (ResumeList::cases() as $list) {
            $ids[$list->value] = $list->items()->pluck('id')->map(intval(...))->all();
        }

        return $ids;
    }

    /**
     * @return array<int, JobPosition> Hunt and application positions by application id, in the order backups list them.
     */
    private function jobPositions(): array
    {
        $positions = [];

        foreach (JobHunt::query()->orderBy('id')->get() as $huntPosition => $hunt) {
            foreach ($hunt->applications()->reorder()->orderBy('id')->pluck('id') as $applicationPosition => $applicationId) {
                $positions[(int) $applicationId] = [$huntPosition, $applicationPosition];
            }
        }

        return $positions;
    }

    /**
     * @param  array<int, array<int, int>>|null  $applicationIds
     */
    private function applicationId(mixed $position, ?array $applicationIds): ?int
    {
        if (! is_array($position) || $applicationIds === null) {
            return null;
        }

        return $applicationIds[$position[0]][$position[1]] ?? null;
    }

    /**
     * @param  array<string, array<int, int>>  $positions
     * @return array<string, list<array{position: int, included: bool}>>
     */
    private function selectionToPositions(Resume $resume, array $positions): array
    {
        $selection = [];

        foreach (ResumeList::cases() as $list) {
            foreach ($resume->selectionFor($list) as $entry) {
                if (isset($positions[$list->value][$entry['id']])) {
                    $selection[$list->value][] = ['position' => $positions[$list->value][$entry['id']], 'included' => $entry['included']];
                }
            }
        }

        return $selection;
    }

    /**
     * @param  array<string, mixed>  $selection
     * @param  array<string, array<int, int>>  $ids
     * @return array<string, list<array{id: int, included: bool}>>
     */
    private function selectionFromPositions(array $selection, array $ids): array
    {
        $restored = [];

        foreach (ResumeList::cases() as $list) {
            /** @var array<int, array{position: int, included: bool}> $entries */
            $entries = $selection[$list->value] ?? [];

            foreach ($entries as $entry) {
                if (isset($ids[$list->value][$entry['position']])) {
                    $restored[$list->value][] = ['id' => $ids[$list->value][$entry['position']], 'included' => (bool) $entry['included']];
                }
            }
        }

        return $restored;
    }

    /**
     * @param  array<int, int>  $experiencePositions
     * @return array<string, mixed>
     */
    private function overridesToPositions(Resume $resume, array $experiencePositions): array
    {
        $overrides = $resume->overrides ?? [];
        $highlights = [];

        foreach ($overrides['highlights'] ?? [] as $experienceId => $override) {
            if (isset($experiencePositions[(int) $experienceId])) {
                $highlights[] = ['position' => $experiencePositions[(int) $experienceId], ...$override];
            }
        }

        return [...array_intersect_key($overrides, array_flip(['headline', 'summary'])), ...($highlights === [] ? [] : ['highlights' => $highlights])];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<int, int>  $experienceIds
     * @return array<string, mixed>
     */
    private function overridesFromPositions(array $overrides, array $experienceIds): array
    {
        $restored = array_intersect_key($overrides, array_flip(['headline', 'summary']));

        /** @var array<int, array{position: int, items: list<string>, base_hash: string}> $highlights */
        $highlights = $overrides['highlights'] ?? [];

        foreach ($highlights as $override) {
            if (isset($experienceIds[$override['position']])) {
                $restored['highlights'][$experienceIds[$override['position']]] = ['items' => array_values($override['items']), 'base_hash' => $override['base_hash']];
            }
        }

        return $restored;
    }
}
