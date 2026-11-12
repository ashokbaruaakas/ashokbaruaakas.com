<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Actions\Assistant\ApplyAssistantProposalAction;
use App\Actions\Assistant\ApplyResumeProposalAction;
use App\Actions\Assistant\UndoAssistantProposalAction;
use App\Actions\Ats\ScoreResumeAction;
use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Ai\Conversations;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use Illuminate\Http\JsonResponse;
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

        toastSuccess($record->addsJob() ? 'Job added.' : 'Change applied.');

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

    /**
     * The resume a proposal would make, with its ATS score, for the owner to look at before applying it.
     */
    public function preview(
        int $proposal,
        ApplyResumeProposalAction $applyResumeProposal,
        ResolveResumeAction $resolveResume,
        BuildPlainTextResumeAction $plainTextResume,
        ScoreResumeAction $scoreResume,
    ): JsonResponse {
        $record = $this->find($proposal);

        abort_unless($record->isForResume(), 404);

        $draft = $applyResumeProposal->preview($record);

        abort_if(! $draft instanceof \App\Models\Resume, 404);

        $portfolio = $resolveResume->handle($draft);
        $ats = $scoreResume->handle($portfolio, $plainTextResume->handle($portfolio), $record->job_keywords);

        return response()->json([
            'portfolio' => $portfolio->toArray(),
            'design' => $draft->design(),
            'ats' => [
                'score' => $ats['score'],
                'jobMatchScore' => $ats['job_match_score'],
                'matchedKeywords' => $ats['matched_keywords'],
                'missingKeywords' => $ats['missing_keywords'],
            ],
        ]);
    }

    private function find(int $proposal, ?ProposalStatus $status = null): AssistantProposal
    {
        return AssistantProposal::query()
            ->whereKey($proposal)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereIn('conversation_id', Conversations::of(authUser())->select('id'))
            ->firstOrFail();
    }
}
