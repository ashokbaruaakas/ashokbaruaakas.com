<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\AddJobApplicationNoteAction;
use App\Enums\JobApplicationEventType;
use App\Http\Requests\JobHunt\JobApplicationNoteRequest;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;

final class JobApplicationNoteController
{
    /**
     * Add a note to a job's timeline.
     */
    public function store(JobApplicationNoteRequest $request, JobHunt $jobHunt, JobApplication $application, AddJobApplicationNoteAction $action): RedirectResponse
    {
        $action->handle(
            $application,
            $request->string('body')->trim()->toString(),
            Date::parse($request->string('occurred_on')->toString()),
        );

        return back();
    }

    /**
     * Remove a note. Status changes stay on the timeline, since the hunt's history is built from them.
     */
    public function destroy(JobHunt $jobHunt, JobApplication $application, JobApplicationEvent $event): RedirectResponse
    {
        abort_unless($event->type === JobApplicationEventType::Note, 404);

        $event->delete();

        return back();
    }
}
