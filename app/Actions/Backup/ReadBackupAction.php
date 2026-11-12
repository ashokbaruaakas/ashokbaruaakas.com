<?php

declare(strict_types=1);

namespace App\Actions\Backup;

use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Enums\JobHuntOutcome;
use App\Enums\MediaKey;
use App\Enums\PortfolioList;
use App\Http\Requests\JobHunt\JobApplicationRequest;
use App\Http\Requests\JobHunt\JobHuntRequest;
use App\Http\Requests\Portfolio\PortfolioCopyUpdateRequest;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
use App\Http\Requests\Settings\AuthenticationSettingsUpdateRequest;
use App\Http\Requests\Settings\MailSettingsUpdateRequest;
use App\Models\Profile;
use App\Settings\AiSettings;
use App\Settings\AuthenticationSettings;
use App\Settings\MailSettings;
use App\Settings\Settings;
use finfo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;
use LogicException;
use ZipArchive;

/**
 * @phpstan-type BackupMedia array{key: MediaKey, extension: string, mime_type: string, original_name: string|null, contents: string}
 * @phpstan-type Backup array{
 *     profile: array<string, mixed>|null,
 *     lists: array<string, array<int, array<string, mixed>>>,
 *     sections: array<string, array<string, mixed>>,
 *     pages: array<string, array<string, mixed>>,
 *     settings: array<string, array<string, mixed>>,
 *     job_hunts: array<int, array<string, mixed>>|null,
 *     resumes: array<int, array<string, mixed>>|null,
 *     resume_files: array<string, string>,
 *     media: list<BackupMedia>,
 * }
 */
final readonly class ReadBackupAction
{
    /** Backups made before resume builds had their own storage list the resume PDF as a file, which a restore rebuilds instead. */
    private const string LEGACY_RESUME_PDF_KEY = 'resume_pdf';

    private const int MAX_ENTRIES = 256;

    private const int MAX_ENTRY_BYTES = 20 * 1024 * 1024;

    private const int MAX_TOTAL_BYTES = 64 * 1024 * 1024;

    /** Uploaded images and favicons are served from this origin, so nothing outside these types can be restored. */
    private const array MIME_TYPES = [
        'webp' => ['image/webp'],
        'jpg' => ['image/jpeg'],
        'png' => ['image/png'],
        'ico' => ['image/vnd.microsoft.icon', 'image/x-icon'],
    ];

    public function __construct(private ResumeBackup $resumeBackup) {}

    /**
     * Read a backup and check everything in it, without changing anything.
     * The archive's entries are only ever read into memory, never extracted,
     * so their names can't reach the filesystem.
     *
     * @return Backup
     *
     * @throws ValidationException When the file is not a complete, valid backup.
     */
    public function handle(string $path): array
    {
        $entries = $this->readEntries($path);
        $manifest = $this->decode($entries, ExportBackupAction::MANIFEST_FILE);

        $this->checkManifest($manifest, $entries);

        $content = $this->decode($entries, ExportBackupAction::CONTENT_FILE);
        $entries = $this->withoutLegacyResumePdf($content, $entries);

        $this->checkContent($content);

        /** @var array<int, array<string, mixed>>|null $resumes */
        $resumes = $content['resumes'] ?? null;
        $resumeFiles = $this->readResumeFiles($this->resumeBackup->files($resumes), $entries);

        /** @var array<int, array{key: string, file: string, original_name?: string|null}> $media */
        $media = $content['media'];

        /** @var Backup $backup */
        $backup = [
            'profile' => $content['profile'],
            'lists' => $content['lists'],
            'sections' => $content['sections'],
            'pages' => $content['pages'],
            'settings' => $content['settings'],
            'job_hunts' => $content['job_hunts'] ?? null,
            'resumes' => $resumes,
            'resume_files' => $resumeFiles,
            'media' => $this->readMedia($media, array_diff_key($entries, $resumeFiles)),
        ];

        return $backup;
    }

    /**
     * @return array<string, string>
     */
    private function readEntries(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY | ZipArchive::CHECKCONS) !== true) {
            $this->fail('The zip file is damaged or incomplete.');
        }

        try {
            if ($zip->numFiles > self::MAX_ENTRIES) {
                $this->fail('The zip file holds more files than a backup can.');
            }

            $totalBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if ($stat === false || $stat['size'] > self::MAX_ENTRY_BYTES) {
                    $this->fail('A file in the zip is too large.');
                }

                $totalBytes += $stat['size'];
            }

            if ($totalBytes > self::MAX_TOTAL_BYTES) {
                $this->fail('The contents of the zip file are too large.');
            }

            $entries = [];

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                $contents = $zip->getFromIndex($index);

                if ($contents === false || array_key_exists($name, $entries)) {
                    $this->fail("{$name} can't be read from the zip.");
                }

                $entries[$name] = $contents;
            }

            return $entries;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<string, string>  $entries
     * @return array<array-key, mixed>
     */
    private function decode(array $entries, string $name): array
    {
        if (! array_key_exists($name, $entries)) {
            $this->fail("It has no {$name}, so it isn't a portfolio backup.");
        }

        try {
            $decoded = json_decode($entries[$name], true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->fail("{$name} isn't valid JSON.");
        }

        if (! is_array($decoded)) {
            $this->fail("{$name} isn't valid.");
        }

        return $decoded;
    }

    /**
     * @param  array<array-key, mixed>  $manifest
     * @param  array<string, string>  $entries
     */
    private function checkManifest(array $manifest, array $entries): void
    {
        if (($manifest['format'] ?? null) !== ExportBackupAction::FORMAT || ! is_int($manifest['version'] ?? null)) {
            $this->fail("It isn't a portfolio backup.");
        }

        if ($manifest['version'] !== ExportBackupAction::VERSION) {
            $this->fail("It uses backup format {$manifest['version']}, and this version of the site reads format " . ExportBackupAction::VERSION . '.');
        }

        $checksums = $manifest['checksums'] ?? null;
        $files = array_diff_key($entries, [ExportBackupAction::MANIFEST_FILE => true]);

        if (! is_array($checksums) || array_diff_key($checksums, $files) !== [] || array_diff_key($files, $checksums) !== []) {
            $this->fail("The files in the zip don't match its manifest.");
        }

        foreach ($files as $name => $contents) {
            if (! is_string($checksums[$name]) || ! hash_equals($checksums[$name], hash('sha256', $contents))) {
                $this->fail("{$name} has been changed or damaged since the backup was made.");
            }
        }
    }

    /**
     * Check the content against the rules the editor and settings forms apply,
     * because it reaches the same public pages.
     *
     * @param  array<array-key, mixed>  $content
     */
    private function checkContent(array $content): void
    {
        $validator = Validator::make($content, $this->rules($content));

        if ($validator->fails()) {
            $this->fail((string) $validator->errors()->first());
        }
    }

    /**
     * @param  array<array-key, mixed>  $content
     * @return array<string, array<mixed>>
     */
    private function rules(array $content): array
    {
        $copyRules = new PortfolioCopyUpdateRequest()->rules();

        $rules = [
            ...$copyRules,
            'profile' => ['present', 'nullable', 'array'],
            'lists' => ['present', 'array'],
            'sections' => ['present', Rule::array(PortfolioCopyUpdateRequest::SECTION_KEYS)],
            'pages' => ['present', Rule::array(PortfolioCopyUpdateRequest::PAGE_KEYS)],
            'settings' => ['present', 'array'],
            'media' => ['present', 'array'],
            'media.*' => ['array'],
            'media.*.key' => ['required', 'string', Rule::enum(MediaKey::class), 'distinct'],
            'media.*.file' => ['required', 'string'],
            'media.*.original_name' => ['nullable', 'string', 'max:255', 'regex:/^[^\/\\\\\x00-\x1f]+$/'],
        ];

        if (is_array($content['profile'] ?? null)) {
            $rules = [...$rules, ...$this->fieldRules(Profile::editableFields(), 'profile.')];
        }

        foreach (PortfolioList::cases() as $list) {
            $prefix = "lists.{$list->value}";
            $rules["{$prefix}"] = ['present', 'array'];
            $rules["{$prefix}.*"] = ['array'];
            $rules = [...$rules, ...$this->fieldRules($list->fields(), "{$prefix}.*.")];

            if ($list->canBeHidden()) {
                $rules["{$prefix}.*.is_visible"] = ['boolean'];
            }
        }

        $rules = [...$rules, ...$this->jobHuntRules(), ...$this->resumeBackup->rules()];

        $settings = is_array($content['settings'] ?? null) ? $content['settings'] : [];

        foreach ($this->settingsRules() as $group => $groupRules) {
            // Backups made before a group existed leave it out, and importing them keeps its current values.
            if (! array_key_exists($group, $settings)) {
                continue;
            }

            $rules["settings.{$group}"] = ['array'];

            foreach ($groupRules as $name => $rule) {
                $rules["settings.{$group}.{$name}"] = is_array($rule) ? $rule : [$rule];
            }
        }

        return $rules;
    }

    /**
     * @param  array<int, PortfolioFieldDTO>  $fields
     * @return array<string, array<int, mixed>>
     */
    private function fieldRules(array $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            foreach ($field->rules() as $name => $rule) {
                $rules["{$prefix}{$name}"] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Backups made before job hunts existed have no `job_hunts` key, and importing them leaves the hunts alone.
     *
     * @return array<string, array<mixed>>
     */
    private function jobHuntRules(): array
    {
        $rules = [
            'job_hunts' => ['sometimes', 'array'],
            'job_hunts.*' => ['array'],
            'job_hunts.*.applications' => ['present', 'array'],
            'job_hunts.*.applications.*' => ['array'],
            'job_hunts.*.applications.*.status' => ['required', Rule::enum(JobApplicationStatus::class)],
            'job_hunts.*.applications.*.events' => ['present', 'array'],
            'job_hunts.*.applications.*.events.*' => ['array'],
            'job_hunts.*.applications.*.events.*.type' => ['required', Rule::enum(JobApplicationEventType::class)],
            'job_hunts.*.applications.*.events.*.from_status' => ['nullable', Rule::enum(JobApplicationStatus::class)],
            'job_hunts.*.applications.*.events.*.to_status' => ['nullable', Rule::enum(JobApplicationStatus::class)],
            'job_hunts.*.applications.*.events.*.body' => ['nullable', 'string', 'max:2000'],
            'job_hunts.*.applications.*.events.*.occurred_on' => ['required', 'date_format:Y-m-d'],
            'job_hunts.*.ended_on' => ['nullable', 'date_format:Y-m-d'],
            'job_hunts.*.outcome' => ['nullable', Rule::enum(JobHuntOutcome::class)],
        ];

        foreach (new JobHuntRequest()->rules() as $name => $rule) {
            $rules["job_hunts.*.{$name}"] = match (true) {
                $name === 'started_on' => ['required', 'date_format:Y-m-d'],
                is_array($rule) => $rule,
                default => [$rule],
            };
        }

        foreach (Arr::except(new JobApplicationRequest()->rules(), 'status') as $name => $rule) {
            $rules["job_hunts.*.applications.*.{$name}"] = is_array($rule) ? $rule : [$rule];
        }

        return $rules;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function settingsRules(): array
    {
        /** @var list<class-string<Settings>> $groups */
        $groups = config()->array('settings.groups');
        $rules = [];

        foreach ($groups as $group) {
            $rules[$group::group()] = match ($group) {
                AuthenticationSettings::class => new AuthenticationSettingsUpdateRequest()->rules(),
                MailSettings::class => new MailSettingsUpdateRequest()->rules(),
                AiSettings::class => new AiSettingsUpdateRequest()->rules(),
                default => throw new LogicException("Backups don't know the [{$group}] settings group."),
            };
        }

        return $rules;
    }

    /**
     * @param  array<int, array{key: string, file: string, original_name?: string|null}>  $media
     * @param  array<string, string>  $entries
     * @return list<BackupMedia>
     */
    private function readMedia(array $media, array $entries): array
    {
        $listedFiles = array_column($media, 'file');
        $storedFiles = array_filter(array_keys($entries), fn (string $name): bool => ! in_array($name, [ExportBackupAction::MANIFEST_FILE, ExportBackupAction::CONTENT_FILE], true));

        if (array_diff($storedFiles, $listedFiles) !== []) {
            $this->fail('The zip holds files the backup does not list.');
        }

        $files = [];

        foreach ($media as $item) {
            $key = MediaKey::from($item['key']);
            $extension = pathinfo($item['file'], PATHINFO_EXTENSION);

            if ($item['file'] !== "files/{$key->value}.{$extension}" || ! in_array($extension, $this->extensionsFor($key), true)) {
                $this->fail("{$item['file']} isn't an allowed file for {$key->value}.");
            }

            if (! array_key_exists($item['file'], $entries)) {
                $this->fail("{$item['file']} is missing from the zip.");
            }

            $contents = $entries[$item['file']];

            if (! in_array(new finfo(FILEINFO_MIME_TYPE)->buffer($contents), self::MIME_TYPES[$extension], true)) {
                $this->fail("{$item['file']} isn't really a .{$extension} file.");
            }

            $files[] = [
                'key' => $key,
                'extension' => $extension,
                'mime_type' => self::MIME_TYPES[$extension][0],
                'original_name' => $item['original_name'] ?? null,
                'contents' => $contents,
            ];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function extensionsFor(MediaKey $key): array
    {
        return match ($key) {
            MediaKey::FaviconIco => ['ico'],
            default => ['webp', 'jpg', 'png'],
        };
    }

    /**
     * @param  array<array-key, mixed>  $content
     * @param  array<string, string>  $entries
     * @return array<string, string>
     */
    private function withoutLegacyResumePdf(array &$content, array $entries): array
    {
        if (! is_array($content['media'] ?? null)) {
            return $entries;
        }

        foreach ($content['media'] as $index => $item) {
            if (is_array($item) && ($item['key'] ?? null) === self::LEGACY_RESUME_PDF_KEY) {
                if (is_string($item['file'] ?? null)) {
                    unset($entries[$item['file']]);
                }

                unset($content['media'][$index]);
            }
        }

        $content['media'] = array_values($content['media']);

        return $entries;
    }

    /**
     * @param  list<string>  $files
     * @param  array<string, string>  $entries
     * @return array<string, string>
     */
    private function readResumeFiles(array $files, array $entries): array
    {
        $contents = [];

        foreach ($files as $file) {
            if (! array_key_exists($file, $entries)) {
                $this->fail("{$file} is missing from the zip.");
            }

            if (new finfo(FILEINFO_MIME_TYPE)->buffer($entries[$file]) !== 'application/pdf') {
                $this->fail("{$file} isn't really a .pdf file.");
            }

            $contents[$file] = $entries[$file];
        }

        return $contents;
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $reason): never
    {
        throw ValidationException::withMessages(['file' => "This backup can't be imported. {$reason}"]);
    }
}
