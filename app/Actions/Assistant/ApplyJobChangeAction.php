<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\JobHunt\ChangeJobApplicationStatusAction;
use App\Actions\JobHunt\SaveJobApplicationAction;
use App\Enums\JobApplicationStatus;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class ApplyJobChangeAction
{
    public function __construct(
        private SaveJobApplicationAction $saveJob,
        private ChangeJobApplicationStatusAction $changeStatus,
    ) {}

    /**
     * Save a proposed job change. A new status goes on the timeline, dated by the proposed applied date when
     * the job is marked applied. Returns false when the job no longer exists.
     */
    public function handle(AssistantProposal $proposal): bool
    {
        $job = $proposal->job();

        if (! $job instanceof JobApplication) {
            return false;
        }

        DB::transaction(function () use ($proposal, $job): void {
            $this->saveJob->update($job, array_diff_key($proposal->changes, ['status' => true]));

            $status = is_string($proposal->changes['status'] ?? null) ? JobApplicationStatus::tryFrom($proposal->changes['status']) : null;
            $appliedOn = $proposal->changes['applied_on'] ?? null;
            $event = $status instanceof JobApplicationStatus
                ? $this->changeStatus->handle($job, $status, $status === JobApplicationStatus::Applied && is_string($appliedOn) ? Carbon::parse($appliedOn) : null)
                : null;

            $proposal->forceFill(['status' => ProposalStatus::Applied, 'applied_at' => now(), 'created_item_id' => $event?->id])->save();
        });

        return true;
    }
}
