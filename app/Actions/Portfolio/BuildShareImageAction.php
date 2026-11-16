<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\ShareCardDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BuildShareImageAction
{
    public const string DISK = 'local';

    private const string DIRECTORY = 'share-images';

    private const int MINIMUM_BYTES = 1024;

    public function __construct(private PrintPdfAction $browser) {}

    /**
     * Get the card as a PNG, rendering it with headless Chrome the first time
     * this content is asked for and keeping it until the content changes.
     *
     * @throws RuntimeException When the card cannot be rendered.
     */
    public function handle(ShareCardDTO $card): string
    {
        $disk = Storage::disk(self::DISK);
        $path = self::DIRECTORY . "/{$card->page->value}-{$card->fingerprint()}.png";

        if (! $disk->exists($path)) {
            Cache::lock("share-image:{$card->page->value}", 60)->block(60, fn () => $this->store($card, $path));
        }

        return (string) $disk->get($path);
    }

    /**
     * Render and store the card unless a request that held the lock first already did,
     * removing the page's older cards.
     */
    private function store(ShareCardDTO $card, string $path): void
    {
        $disk = Storage::disk(self::DISK);

        if ($disk->exists($path)) {
            return;
        }

        $image = $this->render($card);

        throw_unless(mb_strlen($image, '8bit') >= self::MINIMUM_BYTES, RuntimeException::class, 'The share image rendered blank.');

        $stale = array_filter(
            $disk->files(self::DIRECTORY),
            fn (string $file): bool => str_starts_with(basename($file), "{$card->page->value}-") && $file !== $path,
        );

        $disk->delete($stale);
        $disk->put($path, $image);
    }

    private function render(ShareCardDTO $card): string
    {
        $workingDirectory = sys_get_temp_dir() . '/share-' . Str::ulid();
        $htmlPath = "{$workingDirectory}/card.html";

        File::ensureDirectoryExists($workingDirectory);

        try {
            File::put($htmlPath, view(ShareCardDTO::VIEW, [
                'card' => $card,
                'portrait' => $this->portraitDataUri($card),
                'fonts' => $this->fonts(),
                'width' => GetShareImageAction::WIDTH,
                'height' => GetShareImageAction::HEIGHT,
            ])->render());

            return $this->browser->screenshot("file://{$htmlPath}", GetShareImageAction::WIDTH, GetShareImageAction::HEIGHT);
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    private function portraitDataUri(ShareCardDTO $card): ?string
    {
        $disk = Storage::disk('public');

        if ($card->portraitPath === null || ! $disk->exists($card->portraitPath)) {
            return null;
        }

        return 'data:' . $disk->mimeType($card->portraitPath) . ';base64,' . base64_encode((string) $disk->get($card->portraitPath));
    }

    /**
     * Inline the site's built font files, so the card uses the same type as the
     * pages. Chrome blocks fonts loaded from file URLs, hence data URIs. Missing
     * files fall back to system fonts.
     *
     * @return array{heading: string|null, body: string|null}
     */
    private function fonts(): array
    {
        $find = function (string $pattern): ?string {
            $file = collect(File::glob(public_path("build/assets/{$pattern}")))->first();

            return is_string($file) ? 'data:font/woff2;base64,' . base64_encode(File::get($file)) : null;
        };

        return [
            'heading' => $find('space-grotesk-700-normal-*.woff2'),
            'body' => $find('instrument-sans-500-normal-*.woff2'),
        ];
    }
}
