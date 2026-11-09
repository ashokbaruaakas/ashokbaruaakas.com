<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class PrintPdfAction
{
    private const int RENDER_TIMEOUT_SECONDS = 60;

    private const array BROWSER_PATHS = [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    ];

    /**
     * Print a page to PDF with headless Chrome and return the PDF's contents.
     *
     * @throws RuntimeException When the page cannot be printed, or prints smaller than the minimum size.
     */
    public function handle(string $url, int $minimumBytes): string
    {
        $workingDirectory = sys_get_temp_dir() . '/pdf-' . Str::ulid();
        $outputPath = "{$workingDirectory}/output.pdf";

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
                $url,
            ])->throw();

            throw_unless(File::exists($outputPath) && File::size($outputPath) >= $minimumBytes, RuntimeException::class, 'The page rendered blank.');

            return File::get($outputPath);
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
}
