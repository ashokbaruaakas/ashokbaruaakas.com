<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumePdfStatus;
use App\Models\ResumeBuild;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final readonly class BuildResumePdfAction
{
    /** Builds kept per resume besides the ones sent with a job application. */
    public const int KEPT_UNSENT_BUILDS = 10;

    /** A page whose CSS or JS failed to load prints as an empty page of about 1 KB. */
    private const int MINIMUM_PDF_BYTES = 20 * 1024;

    private const int PRINT_LINK_MINUTES = 10;

    public function __construct(
        private GetPortfolioAvailabilityAction $getPortfolioAvailability,
        private ResolveResumeAction $resolveResume,
        private BuildPlainTextResumeAction $buildPlainTextResume,
        private PrintPdfAction $printPdf,
    ) {}

    /**
     * Render a scheduled build of a resume to a PDF. A build whose content matches
     * the resume's current PDF is dropped, so the history only grows on real changes.
     * When the portfolio has no resume left to render, the unsent PDFs are removed.
     *
     * @throws RuntimeException When the page cannot be rendered.
     */
    public function handle(ResumeBuild $build): void
    {
        $resume = $build->resume;

        if (! $this->getPortfolioAvailability->handle()->hasResume) {
            $build->delete();
            $this->removeUnsentBuilds($build->resume_id);
            $resume->update(['page_count' => null]);

            return;
        }

        $snapshot = $this->buildPlainTextResume->handle($this->resolveResume->handle($resume));
        $contentHash = hash('sha256', $resume->template->value . "\n" . $snapshot);
        $current = $resume->currentBuild();

        if ($current?->content_hash === $contentHash) {
            $build->delete();

            return;
        }

        $pdf = $this->printPdf->handle($this->printUrl($build), self::MINIMUM_PDF_BYTES);
        $path = "resume-builds/{$resume->id}/{$build->token}.pdf";

        throw_unless(Storage::disk(ResumeBuild::DISK)->put($path, $pdf), RuntimeException::class, 'Unable to store the PDF.');

        $pageCount = $this->countPages($pdf);

        $build->update([
            'status' => ResumePdfStatus::Ready,
            'template' => $resume->template,
            'page_count' => $pageCount,
            'content_hash' => $contentHash,
            'snapshot' => $snapshot,
            'path' => $path,
            'built_at' => now(),
        ]);

        $resume->update(['page_count' => $pageCount]);

        ResumeBuild::query()->where('resume_id', $resume->id)->where('status', ResumePdfStatus::Failed)->delete();

        $this->pruneUnsentBuilds($resume->id);
    }

    private function printUrl(ResumeBuild $build): string
    {
        return url(URL::temporarySignedRoute('resumes.print', now()->addMinutes(self::PRINT_LINK_MINUTES), [
            'resume' => $build->resume->slug,
            'build' => $build->token,
        ], absolute: false));
    }

    /**
     * Chrome writes the page tree as plain objects, so the root's count can be
     * read from the bytes without a PDF library. The root holds the largest count.
     */
    private function countPages(string $pdf): ?int
    {
        preg_match_all('#<<((?:(?!<<|>>).)*)>>#s', $pdf, $dictionaries);

        $counts = [];

        foreach ($dictionaries[1] as $dictionary) {
            if (preg_match('#/Type\s*/Pages\b#', $dictionary) === 1 && preg_match('#/Count\s+(\d+)#', $dictionary, $count) === 1) {
                $counts[] = (int) $count[1];
            }
        }

        return $counts === [] ? null : max($counts);
    }

    private function pruneUnsentBuilds(int $resumeId): void
    {
        $this->unsentBuilds($resumeId)->skip(self::KEPT_UNSENT_BUILDS)->each($this->deleteBuild(...));
    }

    private function removeUnsentBuilds(int $resumeId): void
    {
        $this->unsentBuilds($resumeId)->each($this->deleteBuild(...));
    }

    /**
     * @return Collection<int, ResumeBuild>
     */
    private function unsentBuilds(int $resumeId): Collection
    {
        return ResumeBuild::query()
            ->where('resume_id', $resumeId)
            ->where('status', ResumePdfStatus::Ready)
            ->whereDoesntHave('sentWith')
            ->orderByDesc('built_at')
            ->orderByDesc('id')
            ->get()
            ->values();
    }

    private function deleteBuild(ResumeBuild $build): void
    {
        if ($build->path !== null) {
            Storage::disk(ResumeBuild::DISK)->delete($build->path);
        }

        $build->delete();
    }
}
