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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AssistantProposalController
{
    /**
     * Apply a pending change. A change to something the owner edited since the assistant read it is applied only
     * with `force`, once the owner has seen that it replaces their edit.
     */
    public function apply(Request $request, int $proposal, ApplyAssistantProposalAction $action): RedirectResponse
    {
        DB::transaction(function () use ($request, $proposal, $action): void {
            $record = $this->find($proposal, ProposalStatus::Pending, lock: true);

            if (! $request->boolean('force') && $record->isStale()) {
                toastError('This changed since the assistant read it. Check the change and apply it anyway to replace your edit.');

                return;
            }

            if (! $action->handle($record)) {
                toastError('The item this change is for no longer exists.');

                return;
            }

            toastSuccess($record->addsJob() ? 'Job added.' : 'Change applied.');
        });

        return back();
    }

    /**
     * Put back what an applied change replaced.
     */
    public function undo(int $proposal, UndoAssistantProposalAction $action): RedirectResponse
    {
        DB::transaction(function () use ($proposal, $action): void {
            if (! $action->handle($this->find($proposal, ProposalStatus::Applied, lock: true))) {
                toastError('The item this change is for no longer exists.');

                return;
            }

            toastSuccess('Change undone.');
        });

        return back();
    }

    /**
     * Turn down a pending change.
     */
    public function dismiss(int $proposal): RedirectResponse
    {
        DB::transaction(fn () => $this->find($proposal, ProposalStatus::Pending, lock: true)->update(['status' => ProposalStatus::Dismissed]));

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

    /**
     * Inside a transaction, `lock` holds the proposal until it commits, so a second click waits and then finds it
     * no longer in the status it needs.
     */
    private function find(int $proposal, ?ProposalStatus $status = null, bool $lock = false): AssistantProposal
    {
        return AssistantProposal::query()
            ->whereKey($proposal)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereIn('conversation_id', Conversations::of(authUser())->select('id'))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->firstOrFail();
    }
}
