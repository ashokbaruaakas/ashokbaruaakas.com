<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BuildResumePdfAction
{
    /** A page whose CSS or JS failed to load prints as an empty page of about 1 KB. */
    private const int MINIMUM_PDF_BYTES = 20 * 1024;

    public function __construct(
        private GetPortfolioAvailabilityAction $getPortfolioAvailability,
        private StoreMediaFileAction $storeMediaFile,
        private PrintPdfAction $printPdf,
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
            Resume::public()->update(['page_count' => null]);

            return;
        }

        $pdf = $this->printPdf->handle(route('resume', ['build' => $token]), self::MINIMUM_PDF_BYTES);

        $this->storeMediaFile->handle(MediaKey::ResumePdf, $pdf, 'pdf', 'application/pdf', $this->fileName());

        Resume::public()->update(['page_count' => $this->countPages($pdf)]);
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
