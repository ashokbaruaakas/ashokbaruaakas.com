<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\Portfolio\SavePortfolioItemAction;
use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\Actions\Portfolio\UpdateProfileAction;
use App\Ai\PortfolioSection;
use App\Enums\PortfolioList;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Model;

final readonly class UndoAssistantProposalAction
{
    public function __construct(
        private UpdateProfileAction $updateProfile,
        private SavePortfolioItemAction $savePortfolioItem,
        private ScheduleResumePdfRebuildAction $scheduleRebuild,
        private UndoJobChangeAction $undoJobChange,
    ) {}

    /**
     * Put back what an applied proposal replaced, or remove the item it added, and make it pending again.
     * Returns false when the profile or item it changed no longer exists.
     */
    public function handle(AssistantProposal $proposal): bool
    {
        if ($proposal->isForJob()) {
            return $this->undoJobChange->handle($proposal);
        }

        if ($proposal->addsJob()) {
            JobApplication::query()->whereKey($proposal->created_item_id)->delete();
            $proposal->forceFill(['status' => ProposalStatus::Pending, 'applied_at' => null, 'created_item_id' => null])->save();

            return true;
        }

        $section = $proposal->portfolioSection();
        $item = $section?->find($proposal->addsItem() ? $proposal->created_item_id : $proposal->item_id);

        if (! $section instanceof PortfolioSection || (! $proposal->addsItem() && ! $item instanceof Model)) {
            return false;
        }

        if (! $section->list instanceof PortfolioList) {
            $this->updateProfile->handle($proposal->before ?? []);
        } elseif ($proposal->addsItem()) {
            $item?->delete();
            $this->scheduleRebuild->handle();
        } elseif ($item instanceof Model) {
            $this->savePortfolioItem->handle($section->list, $proposal->before ?? [], $item);
        }

        $proposal->forceFill(['status' => ProposalStatus::Pending, 'applied_at' => null, 'created_item_id' => null])->save();

        return true;
    }
}
