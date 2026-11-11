<?php

declare(strict_types=1);

namespace App\Actions\Backup;

use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\PortfolioList;
use App\Models\JobHunt;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ResumeBuild;
use App\Settings\Settings;
use App\Settings\SettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type Backup from ReadBackupAction
 */
final readonly class ImportBackupAction
{
    public function __construct(
        private ReadBackupAction $readBackup,
        private SettingsRepository $settings,
        private ScheduleResumePdfRebuildAction $scheduleRebuild,
        private ResumeBackup $resumeBackup,
    ) {}

    /**
     * Replace the portfolio content, job hunts, resumes, settings and files with a backup's. The
     * whole backup is checked first and the database changes in one transaction,
     * so a failed import leaves the site as it was.
     *
     * @throws ValidationException When the file is not a complete, valid backup.
     * @throws RuntimeException When a file cannot be written to storage.
     */
    public function handle(string $path): void
    {
        $backup = $this->readBackup->handle($path);
        $previousMedia = Media::query()->get();
        $previousPaths = $previousMedia->map(fn (Media $media): string => $media->path)->all();
        $writtenPaths = [];
        $writtenResumePaths = [];
        $replacedResumePaths = [];

        try {
            $mediaRows = [];

            foreach ($backup['media'] as $file) {
                $storedPath = Media::pathFor($file['key'], $file['contents'], $file['extension']);

                throw_unless(Storage::disk('public')->put($storedPath, $file['contents']), RuntimeException::class, 'Unable to store the file.');

                $writtenPaths[] = $storedPath;
                $mediaRows[] = [
                    'key' => $file['key'],
                    'disk' => 'public',
                    'path' => $storedPath,
                    'mime_type' => $file['mime_type'],
                    'size' => mb_strlen($file['contents'], '8bit'),
                    'original_name' => $file['original_name'],
                ];
            }

            DB::transaction(function () use ($backup, $mediaRows, &$writtenResumePaths, &$replacedResumePaths): void {
                $applicationIds = $this->restoreContent($backup);
                $replacedResumePaths = $this->resumeBackup->restore($backup['resumes'], $backup['lists'][PortfolioList::Projects->value] ?? [], $applicationIds, $backup['resume_files'], $writtenResumePaths);

                Media::query()->delete();

                foreach ($mediaRows as $row) {
                    Media::query()->forceCreate($row);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values(array_diff($writtenPaths, $previousPaths)));
            Storage::disk(ResumeBuild::DISK)->delete($writtenResumePaths);

            throw $exception;
        }

        // Another request may have cached the old values while the transaction was open.
        $this->settings->forget();

        Storage::disk(ResumeBuild::DISK)->delete($replacedResumePaths);

        foreach ($previousMedia as $media) {
            if ($media->disk !== 'public' || ! in_array($media->path, $writtenPaths, true)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        }

        $this->scheduleRebuild->handle();
    }

    /**
     * @param  Backup  $backup
     * @return array<int, array<int, int>>|null New application ids by hunt and application position, or null when the hunts were left alone.
     */
    private function restoreContent(array $backup): ?array
    {
        Profile::query()->delete();

        if ($backup['profile'] !== null) {
            Profile::query()->forceCreate(array_intersect_key($backup['profile'], array_flip(array_map(fn (PortfolioFieldDTO $field): string => $field->name, Profile::editableFields()))));
        }

        foreach (PortfolioList::cases() as $list) {
            $this->restoreList($list, $backup['lists'][$list->value] ?? []);
        }

        PortfolioSection::query()->delete();

        foreach ($backup['sections'] as $key => $attributes) {
            PortfolioSection::query()->forceCreate(['key' => $key, ...array_intersect_key($attributes, array_flip(['eyebrow', 'title', 'description', 'labels']))]);
        }

        PageContent::query()->delete();

        foreach ($backup['pages'] as $key => $attributes) {
            PageContent::query()->forceCreate(['key' => $key, ...array_intersect_key($attributes, array_flip(['title', 'description', 'image_alt']))]);
        }

        /** @var list<class-string<Settings>> $groups */
        $groups = config()->array('settings.groups');

        foreach ($groups as $group) {
            if (! array_key_exists($group::group(), $backup['settings'])) {
                continue;
            }

            $restored = $group::fromPayload($backup['settings'][$group::group()]);

            $this->settings->save($restored->fill($this->settings->load($group)->secrets()));
        }

        return $backup['job_hunts'] === null ? null : $this->restoreJobHunts($backup['job_hunts']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $hunts
     * @return array<int, array<int, int>>
     */
    private function restoreJobHunts(array $hunts): array
    {
        JobHunt::query()->delete();
        $applicationIds = [];

        foreach (array_values($hunts) as $huntPosition => $attributes) {
            $hunt = JobHunt::query()->forceCreate(array_intersect_key($attributes, array_flip(ExportBackupAction::JOB_HUNT_ATTRIBUTES)));

            /** @var array<int, array<string, mixed>> $applications */
            $applications = $attributes['applications'] ?? [];

            foreach (array_values($applications) as $applicationPosition => $applicationAttributes) {
                $application = $hunt->applications()->forceCreate(array_intersect_key($applicationAttributes, array_flip(ExportBackupAction::JOB_APPLICATION_ATTRIBUTES)));
                $applicationIds[$huntPosition][$applicationPosition] = $application->id;

                /** @var array<int, array<string, mixed>> $events */
                $events = $applicationAttributes['events'] ?? [];

                foreach ($events as $event) {
                    $application->events()->forceCreate(array_intersect_key($event, array_flip(ExportBackupAction::JOB_APPLICATION_EVENT_ATTRIBUTES)));
                }
            }
        }

        return $applicationIds;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function restoreList(PortfolioList $list, array $items): void
    {
        $model = $list->model();

        $model::query()->delete();

        foreach (array_values($items) as $sortOrder => $attributes) {
            $item = $model::query()->forceCreate([...array_intersect_key($attributes, array_flip($list->storedAttributes())), 'sort_order' => $sortOrder]);

            if ($item instanceof Project) {
                /** @var array<int, array<string, mixed>> $links */
                $links = $attributes['links'] ?? [];

                foreach (array_values($links) as $linkOrder => $link) {
                    $item->links()->create([...array_intersect_key($link, array_flip(['label', 'url', 'is_primary'])), 'sort_order' => $linkOrder]);
                }
            }
        }
    }
}
