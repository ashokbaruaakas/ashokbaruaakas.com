<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\Portfolio\PrintPdfAction;
use App\Models\CoverLetter;
use App\Models\Profile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BuildCoverLetterPdfAction
{
    /** A letter prints at well over this size, so anything smaller failed to render. */
    private const int MINIMUM_PDF_BYTES = 1024;

    public function __construct(private PrintPdfAction $printPdf) {}

    /**
     * Print the letter under the owner's name and contact details, and return the PDF's contents.
     *
     * @throws RuntimeException When the letter cannot be printed.
     */
    public function handle(CoverLetter $letter): string
    {
        $profile = Profile::query()->first();
        $page = sys_get_temp_dir() . '/cover-letter-' . Str::ulid() . '.html';

        File::put($page, view('pdf.cover-letter', [
            'letter' => $letter,
            'profile' => $profile,
            'title' => $profile->tagline ?? $profile?->headline,
            'contact' => $this->contactDetails($profile),
            'date' => $letter->created_at?->format('F j, Y'),
        ])->render());

        try {
            return $this->printPdf->handle("file://{$page}", self::MINIMUM_PDF_BYTES);
        } finally {
            File::delete($page);
        }
    }

    /**
     * The download's name, such as Ada-Lovelace-Cover-Letter-Acme.pdf.
     */
    public function fileName(CoverLetter $letter): string
    {
        $name = Str::of(Profile::query()->first()->name ?? '')->slug()->title()->toString();
        $company = $letter->job->company ?? $letter->title;

        return collect([$name, 'Cover-Letter', Str::of($company)->slug()->title()->toString()])->filter()->implode('-') . '.pdf';
    }

    /**
     * The contact lines under the owner's name, labelled the way the resume labels them.
     *
     * @return array<string, string>
     */
    private function contactDetails(?Profile $profile): array
    {
        if (! $profile instanceof Profile) {
            return [];
        }

        $location = filled($profile->location) && filled($profile->timezone_label)
            ? "{$profile->location} ({$profile->timezone_label})"
            : $profile->location;

        return array_filter([
            'Location' => $location,
            'Phone' => $profile->phone_display ?? $profile->phone,
            'Email' => $profile->email,
            'Website' => $profile->website_url,
        ], filled(...));
    }
}
