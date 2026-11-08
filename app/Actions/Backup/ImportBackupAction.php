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
    ) {}

    /**
     * Replace the portfolio content, job hunts, settings and files with a backup's. The
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

            DB::transaction(function () use ($backup, $mediaRows): void {
                $this->restoreContent($backup);

                Media::query()->delete();

                foreach ($mediaRows as $row) {
                    Media::query()->forceCreate($row);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values(array_diff($writtenPaths, $previousPaths)));

            throw $exception;
        }

        // Another request may have cached the old values while the transaction was open.
        $this->settings->forget();

        foreach ($previousMedia as $media) {
            if ($media->disk !== 'public' || ! in_array($media->path, $writtenPaths, true)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        }

        $this->scheduleRebuild->handle();
    }

    /**
     * @param  Backup  $backup
     */
    private function restoreContent(array $backup): void
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
            $this->settings->save($group::fromPayload($backup['settings'][$group::group()] ?? []));
        }

        if ($backup['job_hunts'] !== null) {
            $this->restoreJobHunts($backup['job_hunts']);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $hunts
     */
    private function restoreJobHunts(array $hunts): void
    {
        JobHunt::query()->delete();

        foreach ($hunts as $attributes) {
            $hunt = JobHunt::query()->forceCreate(array_intersect_key($attributes, array_flip(ExportBackupAction::JOB_HUNT_ATTRIBUTES)));

            /** @var array<int, array<string, mixed>> $applications */
            $applications = $attributes['applications'] ?? [];

            foreach ($applications as $applicationAttributes) {
                $application = $hunt->applications()->forceCreate(array_intersect_key($applicationAttributes, array_flip(ExportBackupAction::JOB_APPLICATION_ATTRIBUTES)));

                /** @var array<int, array<string, mixed>> $events */
                $events = $applicationAttributes['events'] ?? [];

                foreach ($events as $event) {
                    $application->events()->forceCreate(array_intersect_key($event, array_flip(ExportBackupAction::JOB_APPLICATION_EVENT_ATTRIBUTES)));
                }
            }
        }
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
