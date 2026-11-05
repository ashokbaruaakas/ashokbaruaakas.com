<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Models\Media;
use App\Models\Profile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BuildResumePdfAction
{
    /** A page whose CSS or JS failed to load prints as an empty page of about 1 KB. */
    private const int MINIMUM_PDF_BYTES = 20 * 1024;

    private const int RENDER_TIMEOUT_SECONDS = 60;

    private const array BROWSER_PATHS = [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    ];

    public function __construct(
        private GetPortfolioAvailabilityAction $getPortfolioAvailability,
        private StoreMediaFileAction $storeMediaFile,
    ) {}

    /**
     * Render the resume page to a PDF and store it, or remove the stored PDF
     * when there is no longer a resume page to render. The token keeps a shared
     * cache from answering with a page from before the latest change.
     *
     * @throws RuntimeException When the page cannot be rendered.
     */
    public function handle(string $token): void
    {
        if (! $this->getPortfolioAvailability->handle()->hasResume) {
            $this->removeStoredPdf();

            return;
        }

        $workingDirectory = sys_get_temp_dir() . "/resume-pdf-{$token}";
        $outputPath = "{$workingDirectory}/resume.pdf";

        File::ensureDirectoryExists($workingDirectory);

        try {
            Process::timeout(self::RENDER_TIMEOUT_SECONDS)->run([
                $this->browserPath(),
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--no-pdf-header-footer',
                '--virtual-time-budget=10000',
                "--user-data-dir={$workingDirectory}/profile",
                "--print-to-pdf={$outputPath}",
                route('resume', ['build' => $token]),
            ])->throw();

            throw_unless(File::exists($outputPath) && File::size($outputPath) >= self::MINIMUM_PDF_BYTES, RuntimeException::class, 'The resume page rendered blank.');

            $this->storeMediaFile->handle(MediaKey::ResumePdf, File::get($outputPath), 'pdf', 'application/pdf', $this->fileName());
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    private function browserPath(): string
    {
        $configuredPath = config('services.chrome.path');
        $candidates = is_string($configuredPath) ? [$configuredPath, ...self::BROWSER_PATHS] : self::BROWSER_PATHS;

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No Chrome or Chromium found. Set CHROME_PATH.');
    }

    private function fileName(): string
    {
        $name = Str::of(Profile::query()->firstOrFail()->name)->slug()->title();

        return "{$name}-Resume.pdf";
    }

    private function removeStoredPdf(): void
    {
        $media = Media::query()->where('key', MediaKey::ResumePdf)->first();

        if ($media === null) {
            return;
        }

        Storage::disk($media->disk)->delete($media->path);

        $media->delete();
    }
}
