<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;

final readonly class UndoJobChangeAction
{
    /**
     * Put back the job values an applied change replaced and remove the timeline entry it added, then make it
     * pending again. Returns false when the job no longer exists.
     */
    public function handle(AssistantProposal $proposal): bool
    {
        $job = $proposal->job();

        if (! $job instanceof JobApplication) {
            return false;
        }

        DB::transaction(function () use ($proposal, $job): void {
            $job->forceFill($proposal->before ?? [])->save();

            if ($proposal->created_item_id !== null) {
                $job->events()->whereKey($proposal->created_item_id)->delete();
            }

            $proposal->forceFill(['status' => ProposalStatus::Pending, 'applied_at' => null, 'created_item_id' => null])->save();
        });

        return true;
    }
}
