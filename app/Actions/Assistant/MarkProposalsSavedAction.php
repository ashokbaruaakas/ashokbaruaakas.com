<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Ai\Conversations;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;

final readonly class MarkProposalsSavedAction
{
    /**
     * Mark the owner's pending proposals that were loaded into an editor as applied once the editor saves them,
     * with the item the save created for a proposal that adds one, so undoing it removes that item.
     *
     * @param  array<array-key, mixed>  $ids
     */
    public function handle(array $ids, ?int $createdItemId = null): void
    {
        $ids = array_values(array_filter($ids, is_int(...)));

        if ($ids === []) {
            return;
        }

        AssistantProposal::query()
            ->whereKey($ids)
            ->where('status', ProposalStatus::Pending)
            ->whereIn('conversation_id', Conversations::of(authUser())->select('id'))
            ->get()
            ->each(fn (AssistantProposal $proposal): bool => $proposal->forceFill([
                'status' => ProposalStatus::Applied,
                'applied_at' => now(),
                'created_item_id' => $proposal->addsItem() ? $createdItemId : $proposal->created_item_id,
            ])->save());
    }
}
