<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\Enums\MediaKey;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResumePdfController
{
    /**
     * Download the resume PDF.
     */
    public function show(): StreamedResponse
    {
        $media = Media::query()->where('key', MediaKey::ResumePdf)->firstOrFail();

        return Storage::disk($media->disk)->download($media->path, $media->original_name);
    }

    /**
     * Rebuild the resume PDF without waiting.
     */
    public function store(ScheduleResumePdfRebuildAction $scheduleRebuild): RedirectResponse
    {
        $scheduleRebuild->handle(immediately: true);

        return back();
    }
}
