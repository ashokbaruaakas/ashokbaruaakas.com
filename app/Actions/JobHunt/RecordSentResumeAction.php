<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Models\JobApplication;
use App\Models\Resume;

final readonly class RecordSentResumeAction
{
    /**
     * Once a job has been applied to, remember which PDF went with it: the
     * current build of the resume tailored for the job, or else the public one.
     * A build the owner already chose is kept.
     */
    public function handle(JobApplication $application): void
    {
        if ($application->resume_build_id !== null || ! $application->status->hasApplied()) {
            return;
        }

        $tailored = Resume::query()->active()->where('job_application_id', $application->id)->latest('id')->first();
        $build = $tailored?->currentBuild() ?? Resume::publicPdf();

        if ($build !== null) {
            $application->update(['resume_build_id' => $build->id]);
        }
    }
}
