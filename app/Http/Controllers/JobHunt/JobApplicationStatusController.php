<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\ChangeJobApplicationStatusAction;
use App\Enums\JobApplicationStatus;
use App\Http\Requests\JobHunt\JobApplicationStatusRequest;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;

final class JobApplicationStatusController
{
    /**
     * Move a job to another status.
     */
    public function update(JobApplicationStatusRequest $request, JobHunt $jobHunt, JobApplication $application, ChangeJobApplicationStatusAction $action): RedirectResponse
    {
        $action->handle($application, $request->enum('status', JobApplicationStatus::class) ?? $application->status);

        return back();
    }
}
