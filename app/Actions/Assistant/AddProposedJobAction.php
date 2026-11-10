<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\JobHunt\SaveJobApplicationAction;
use App\Enums\JobApplicationStatus;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobHunt;
use Illuminate\Support\Facades\DB;

final readonly class AddProposedJobAction
{
    public function __construct(private SaveJobApplicationAction $saveJob) {}

    /**
     * Add the proposed job to its hunt. Returns false when the hunt no longer exists.
     */
    public function handle(AssistantProposal $proposal): bool
    {
        $hunt = $proposal->hunt();

        if (! $hunt instanceof JobHunt) {
            return false;
        }

        $status = is_string($proposal->changes['status'] ?? null) ? JobApplicationStatus::tryFrom($proposal->changes['status']) : null;

        DB::transaction(function () use ($proposal, $hunt, $status): void {
            $job = $this->saveJob->create($hunt, $status ?? JobApplicationStatus::Wishlist, $proposal->changes);

            $proposal->forceFill(['status' => ProposalStatus::Applied, 'applied_at' => now(), 'created_item_id' => $job->id])->save();
        });

        return true;
    }
}
