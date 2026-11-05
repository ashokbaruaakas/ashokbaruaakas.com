<?php

declare(strict_types=1);

namespace App\Actions\Backup;

use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\PortfolioFieldType;
use App\Enums\PortfolioList;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Settings\Settings;
use App\Settings\SettingsRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final readonly class ExportBackupAction
{
    public const string FORMAT = 'portfolio-backup';

    public const int VERSION = 1;

    public const string MANIFEST_FILE = 'manifest.json';

    public const string CONTENT_FILE = 'content.json';

    public function __construct(private SettingsRepository $settings) {}

    /**
     * Write the portfolio content, settings and files to a temporary zip file.
     * Accounts and sign-in details are left out, so an import can't lock the
     * owner out or restore old credentials.
     *
     * @return string The path of the zip file, which the caller must delete.
     *
     * @throws RuntimeException When a file is missing or the zip cannot be written.
     */
    public function handle(): string
    {
        $files = [];
        $media = [];

        foreach (Media::query()->orderBy('key')->get() as $item) {
            $contents = Storage::disk($item->disk)->get($item->path);

            throw_unless(is_string($contents), RuntimeException::class, "The file for {$item->key->value} is missing from storage. Replace or remove it, then try again.");

            $name = "files/{$item->key->value}." . File::extension($item->path);
            $files[$name] = $contents;
            $media[] = ['key' => $item->key->value, 'file' => $name, 'original_name' => $item->original_name];
        }

        $files = [self::CONTENT_FILE => $this->encode([
            'profile' => $this->profile(),
            'lists' => $this->lists(),
            'sections' => $this->copy(PortfolioSection::class, ['eyebrow', 'title', 'description', 'labels']),
            'pages' => $this->copy(PageContent::class, ['title', 'description', 'image_alt']),
            'settings' => $this->settingsGroups(),
            'media' => $media,
        ]), ...$files];

        return $this->writeZip([self::MANIFEST_FILE => $this->encode([
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'created_at' => now()->toIso8601String(),
            'checksums' => array_map(fn (string $contents): string => hash('sha256', $contents), $files),
        ]), ...$files]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function profile(): ?array
    {
        $profile = Profile::query()->first();

        if ($profile === null) {
            return null;
        }

        return array_intersect_key($profile->toArray(), array_flip(array_map(fn (PortfolioFieldDTO $field): string => $field->name, Profile::editableFields())));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function lists(): array
    {
        $lists = [];

        foreach (PortfolioList::cases() as $list) {
            $query = $list->model()::query()->orderBy('sort_order')->orderBy('id');

            if ($list === PortfolioList::Projects) {
                $query->with('links');
            }

            $lists[$list->value] = $query->get()->map(fn (Model $item): array => $this->listItem($list, $item))->all();
        }

        return $lists;
    }

    /**
     * Select values are exported as strings, the form the editor submits them in.
     *
     * @return array<string, mixed>
     */
    private function listItem(PortfolioList $list, Model $item): array
    {
        $attributes = array_intersect_key($item->toArray(), array_flip($list->storedAttributes()));

        foreach ($list->fields() as $field) {
            if ($field->type === PortfolioFieldType::Select && isset($attributes[$field->name]) && is_scalar($attributes[$field->name])) {
                $attributes[$field->name] = (string) $attributes[$field->name];
            }
        }

        if ($item instanceof Project) {
            $attributes['links'] = $item->links->map(fn (ProjectLink $link): array => array_intersect_key($link->toArray(), array_flip(['label', 'url', 'is_primary'])))->all();
        }

        return $attributes;
    }

    /**
     * @param  class-string<PortfolioSection|PageContent>  $model
     * @param  array<int, string>  $columns
     * @return array<string, array<string, mixed>>
     */
    private function copy(string $model, array $columns): array
    {
        $copy = [];

        foreach ($model::query()->orderBy('key')->get() as $item) {
            $copy[$item->key] = array_intersect_key($item->toArray(), array_flip($columns));
        }

        return $copy;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function settingsGroups(): array
    {
        /** @var list<class-string<Settings>> $groups */
        $groups = config()->array('settings.groups');
        $settings = [];

        foreach ($groups as $group) {
            $settings[$group::group()] = $this->settings->load($group)->toArray();
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function writeZip(array $entries): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'portfolio-backup-');
        $zip = new ZipArchive();

        throw_unless($zip->open($path, ZipArchive::OVERWRITE) === true, RuntimeException::class, 'Unable to create the backup file.');

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        throw_unless($zip->close(), RuntimeException::class, 'Unable to write the backup file.');

        return $path;
    }
}
