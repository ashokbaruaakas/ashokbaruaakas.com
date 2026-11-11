<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Http\Requests\JobHunt\JobApplicationSentResumeRequest;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;

final class JobApplicationSentResumeController
{
    /**
     * Record which resume PDF went with a job.
     */
    public function update(JobApplicationSentResumeRequest $request, JobHunt $jobHunt, JobApplication $application): RedirectResponse
    {
        $application->update(['resume_build_id' => $request->filled('resume_build_id') ? $request->integer('resume_build_id') : null]);

        toastSuccess('Saved.');

        return back();
    }
}
