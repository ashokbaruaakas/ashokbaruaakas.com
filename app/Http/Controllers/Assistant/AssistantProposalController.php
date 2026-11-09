<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Actions\Assistant\ApplyAssistantProposalAction;
use App\Actions\Assistant\UndoAssistantProposalAction;
use App\Ai\Conversations;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use Illuminate\Http\RedirectResponse;

final class AssistantProposalController
{
    /**
     * Apply a pending change to the portfolio.
     */
    public function apply(int $proposal, ApplyAssistantProposalAction $action): RedirectResponse
    {
        $record = $this->find($proposal, ProposalStatus::Pending);

        if (! $action->handle($record)) {
            toastError('The item this change is for no longer exists.');

            return back();
        }

        toastSuccess('Change applied.');

        return back();
    }

    /**
     * Put back what an applied change replaced.
     */
    public function undo(int $proposal, UndoAssistantProposalAction $action): RedirectResponse
    {
        $record = $this->find($proposal, ProposalStatus::Applied);

        if (! $action->handle($record)) {
            toastError('The item this change is for no longer exists.');

            return back();
        }

        toastSuccess('Change undone.');

        return back();
    }

    /**
     * Turn down a pending change.
     */
    public function dismiss(int $proposal): RedirectResponse
    {
        $this->find($proposal, ProposalStatus::Pending)->update(['status' => ProposalStatus::Dismissed]);

        return back();
    }

    private function find(int $proposal, ProposalStatus $status): AssistantProposal
    {
        return AssistantProposal::query()
            ->whereKey($proposal)
            ->where('status', $status)
            ->whereIn('conversation_id', Conversations::of(authUser())->select('id'))
            ->firstOrFail();
    }
}
