<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\Portfolio\SaveResumeAction;
use App\Ai\ResumeChange;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\Resume;

final readonly class ApplyResumeProposalAction
{
    public function __construct(
        private SaveResumeAction $saveResume,
        private ResumeChange $resumeChange,
    ) {}

    /**
     * Create the proposed resume or save the proposed change to one. Returns false when the resume no longer exists.
     */
    public function apply(AssistantProposal $proposal): bool
    {
        if ($proposal->section === ResumeChange::NEW_RESUME_SECTION) {
            /** @var array{name: string, job_application_id?: int|null} $changes */
            $changes = $proposal->changes;
            $resume = $this->saveResume->create($changes['name'], jobApplicationId: $changes['job_application_id'] ?? null);
            $proposal->created_item_id = $resume->id;
        } else {
            $resume = Resume::query()->active()->find($proposal->item_id);

            if (! $resume instanceof Resume) {
                return false;
            }
        }

        $this->saveResume->update($resume, $this->resumeChange->toEditorData($resume, ResumeChange::fromStored($proposal->changes)), rebuildNow: true);

        $proposal->forceFill(['status' => ProposalStatus::Applied, 'applied_at' => now()])->save();

        return true;
    }

    /**
     * The resume as it would be once the proposal is applied, without saving it.
     * Null when the resume it changes no longer exists.
     */
    public function preview(AssistantProposal $proposal): ?Resume
    {
        if ($proposal->section === ResumeChange::NEW_RESUME_SECTION) {
            /** @var array{name: string, job_application_id?: int|null} $changes */
            $changes = $proposal->changes;
            $resume = $this->saveResume->blank($changes['name'], jobApplicationId: $changes['job_application_id'] ?? null);
        } else {
            $resume = Resume::query()->active()->find($proposal->item_id);

            if (! $resume instanceof Resume) {
                return null;
            }
        }

        return $this->saveResume->draft($resume, $this->resumeChange->toEditorData($resume, ResumeChange::fromStored($proposal->changes)));
    }

    /**
     * Put the resume back as it was, or remove the resume the proposal created. Returns false when
     * that is no longer possible: the resume is gone, public, or kept for a PDF sent with a job.
     */
    public function undo(AssistantProposal $proposal): bool
    {
        if ($proposal->section === ResumeChange::NEW_RESUME_SECTION) {
            $resume = Resume::query()->find($proposal->created_item_id);

            if ($resume instanceof Resume) {
                if ($resume->is_public || $resume->hasSentBuilds()) {
                    return false;
                }

                $resume->deleteWithPdfs();
            }
        } else {
            $resume = Resume::query()->active()->find($proposal->item_id);

            if (! $resume instanceof Resume) {
                return false;
            }

            $this->saveResume->update($resume, $this->resumeChange->toEditorData($resume, ResumeChange::fromStored($proposal->before ?? [])), rebuildNow: true);
        }

        $proposal->forceFill(['status' => ProposalStatus::Pending, 'applied_at' => null, 'created_item_id' => null])->save();

        return true;
    }
}
