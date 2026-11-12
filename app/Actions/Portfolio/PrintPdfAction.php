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
        $workingDirectory = $this->workingDirectory();
        $outputPath = "{$workingDirectory}/output.pdf";

        try {
            $this->runBrowser($workingDirectory, ["--print-to-pdf={$outputPath}", $url]);

            throw_unless(File::exists($outputPath) && File::size($outputPath) >= $minimumBytes, RuntimeException::class, 'The page rendered blank.');

            return File::get($outputPath);
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    /**
     * Load a page in headless Chrome, let its scripts run, and return the page's HTML as they left it.
     *
     * @throws RuntimeException When the page cannot be loaded.
     */
    public function dumpDom(string $url): string
    {
        $workingDirectory = $this->workingDirectory();

        try {
            return $this->runBrowser($workingDirectory, ['--dump-dom', $url]);
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    private function workingDirectory(): string
    {
        $workingDirectory = sys_get_temp_dir() . '/pdf-' . Str::ulid();

        File::ensureDirectoryExists($workingDirectory);

        return $workingDirectory;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runBrowser(string $workingDirectory, array $arguments): string
    {
        return Process::timeout(self::RENDER_TIMEOUT_SECONDS)->run([
            $this->browserPath(),
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--no-pdf-header-footer',
            '--virtual-time-budget=10000',
            "--user-data-dir={$workingDirectory}/profile",
            ...$arguments,
        ])->throw()->output();
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
