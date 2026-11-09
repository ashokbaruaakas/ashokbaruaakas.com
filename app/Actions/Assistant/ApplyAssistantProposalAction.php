<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Actions\Portfolio\SavePortfolioItemAction;
use App\Actions\Portfolio\UpdateProfileAction;
use App\Ai\PortfolioSection;
use App\Enums\PortfolioList;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use Illuminate\Database\Eloquent\Model;

final readonly class ApplyAssistantProposalAction
{
    public function __construct(
        private UpdateProfileAction $updateProfile,
        private SavePortfolioItemAction $savePortfolioItem,
    ) {}

    /**
     * Save the proposed values to the portfolio. Returns false when the profile or item it changes does not exist.
     */
    public function handle(AssistantProposal $proposal): bool
    {
        $section = $proposal->portfolioSection();
        $item = $section?->find($proposal->item_id);

        if (! $section instanceof PortfolioSection || (! $proposal->addsItem() && ! $item instanceof Model)) {
            return false;
        }

        if (! $section->list instanceof PortfolioList) {
            $this->updateProfile->handle($proposal->changes);
        } elseif ($item instanceof Model) {
            $this->savePortfolioItem->handle($section->list, $proposal->changes, $item);
        } else {
            $createdId = $this->savePortfolioItem->handle($section->list, $proposal->changes)->getKey();
            $proposal->created_item_id = is_int($createdId) ? $createdId : null;
        }

        $proposal->forceFill(['status' => ProposalStatus::Applied, 'applied_at' => now()])->save();

        return true;
    }
}
