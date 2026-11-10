<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class ChangeJobApplicationStatusAction
{
    /**
     * Move a job to a new status and record the change on its timeline. The applied date is
     * filled in the first time the job leaves the wishlist for an applied status. Returns the
     * timeline entry, or null when the job already has that status.
     */
    public function handle(JobApplication $application, JobApplicationStatus $status, ?CarbonInterface $occurredOn = null): ?JobApplicationEvent
    {
        $previousStatus = $application->status;

        if ($previousStatus === $status) {
            return null;
        }

        $occurredOn ??= today();

        return DB::transaction(function () use ($application, $status, $previousStatus, $occurredOn): JobApplicationEvent {
            $application->fill([
                'status' => $status,
                'applied_on' => $status->hasApplied() ? ($application->applied_on ?? $occurredOn) : $application->applied_on,
            ])->save();

            return $application->events()->create([
                'type' => JobApplicationEventType::StatusChange,
                'from_status' => $previousStatus,
                'to_status' => $status,
                'occurred_on' => $occurredOn,
            ]);
        });
    }
}
