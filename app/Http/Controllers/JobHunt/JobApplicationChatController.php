<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Ai\Conversations;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;

final class JobApplicationChatController
{
    /**
     * Open the latest assistant conversation about a job, or start a new one about it.
     */
    public function show(JobHunt $jobHunt, JobApplication $application): RedirectResponse
    {
        $conversation = Conversations::of(authUser())
            ->where('job_application_id', $application->id)
            ->latest('updated_at')
            ->value('id');

        return $conversation === null
            ? to_route('assistant.show', ['job' => $application->id])
            : to_route('assistant.show', ['conversation' => $conversation]);
    }
}
