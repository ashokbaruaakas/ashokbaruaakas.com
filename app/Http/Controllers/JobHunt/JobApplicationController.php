<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\SaveJobApplicationAction;
use App\Enums\JobApplicationStatus;
use App\Http\Requests\JobHunt\JobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;

final class JobApplicationController
{
    /**
     * Add a job to a hunt.
     */
    public function store(JobApplicationRequest $request, JobHunt $jobHunt, SaveJobApplicationAction $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        $action->create($jobHunt, $request->enum('status', JobApplicationStatus::class) ?? JobApplicationStatus::Applied, $attributes);

        toastSuccess('Job added.');

        return back();
    }

    /**
     * Update a job's details.
     */
    public function update(JobApplicationRequest $request, JobHunt $jobHunt, JobApplication $application, SaveJobApplicationAction $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        $action->update($application, $attributes);

        toastSuccess('Saved.');

        return back();
    }

    /**
     * Remove a job and its timeline.
     */
    public function destroy(JobHunt $jobHunt, JobApplication $application): RedirectResponse
    {
        $application->delete();

        toastSuccess('Job removed.');

        return back();
    }
}
