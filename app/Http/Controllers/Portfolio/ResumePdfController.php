<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\ResumeBuild;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResumePdfController
{
    /**
     * Download the public resume's current PDF.
     */
    public function show(): StreamedResponse
    {
        $build = Resume::publicPdf();

        abort_if($build === null, 404);

        return $this->download($build);
    }

    /**
     * Download one build of any resume, for the owner.
     */
    public function download(ResumeBuild $build): StreamedResponse
    {
        abort_if($build->path === null, 404);

        $name = Str::of(Profile::query()->value('name') ?? 'Resume')->slug()->title();

        return Storage::disk(ResumeBuild::DISK)->download($build->path, "{$name}-Resume.pdf");
    }

    /**
     * Rebuild the public resume's PDF without waiting.
     */
    public function store(ScheduleResumePdfRebuildAction $scheduleRebuild): RedirectResponse
    {
        $scheduleRebuild->handle(immediately: true, resume: Resume::public());

        return back();
    }
}
