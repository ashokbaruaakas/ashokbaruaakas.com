<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\Portfolio\SaveResumeAction;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final class JobApplicationResumeController
{
    /**
     * Start a resume tailored for a job as a copy of the public resume, and open
     * it with the assistant beside it. A job that already has one opens that.
     */
    public function store(JobHunt $jobHunt, JobApplication $application, SaveResumeAction $saveResume): RedirectResponse
    {
        $existing = Resume::query()->active()->where('job_application_id', $application->id)->latest('id')->first();

        if ($existing instanceof Resume) {
            return to_route('resumes.edit', ['resume' => $existing, 'assistant' => 1]);
        }

        $public = Resume::public();
        $resume = $saveResume->create(Str::limit("{$application->company} — {$application->role}", 100, ''), $public, $application->id);

        toastSuccess("Resume created from {$public->name}, tailored for {$application->company}.");

        return to_route('resumes.edit', ['resume' => $resume, 'assistant' => 1]);
    }
}
