<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Models\Resume;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @phpstan-import-type ResumeData from SaveResumeAction
 */
final readonly class MeasureResumeAction
{
    private const int LINK_MINUTES = 5;

    public function __construct(private PrintPdfAction $printPdf) {}

    public static function cacheKey(string $token): string
    {
        return "resume-measure:{$token}";
    }

    /**
     * Lay a resume out on A4 pages as its PDF would, with the given editor data
     * in place of what is saved, and say how many pages it takes and how full
     * the last one is, from 0 to 1.
     *
     * @param  ResumeData  $data
     * @return array{pages: int, lastPageFill: float}
     *
     * @throws RuntimeException When the page cannot be laid out.
     */
    public function handle(Resume $resume, array $data): array
    {
        $token = Str::random(40);

        Cache::put(self::cacheKey($token), $data, now()->addMinutes(self::LINK_MINUTES));

        try {
            $html = $this->printPdf->dumpDom(url(URL::temporarySignedRoute('resumes.measure', now()->addMinutes(self::LINK_MINUTES), [
                'resume' => $resume->slug,
                'token' => $token,
            ], absolute: false)));
        } finally {
            Cache::forget(self::cacheKey($token));
        }

        throw_unless(
            preg_match('/data-resume-pages="(\d+)"/', $html, $pages) === 1 && preg_match('/data-resume-last-page-fill="([\d.]+)"/', $html, $fill) === 1,
            RuntimeException::class,
            'The resume could not be laid out.',
        );

        return ['pages' => (int) $pages[1], 'lastPageFill' => round((float) $fill[1], 2)];
    }
}
