<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\PortfolioAvailabilityDTO;
use App\Models\Experience;
use App\Models\OpenSourceContribution;
use App\Models\Profile;

final readonly class GetPortfolioAvailabilityAction
{
    public function __construct(private GetPortfolioContentStatusAction $getContentStatus) {}

    /**
     * Determine which public portfolio pages have enough data to be shown.
     */
    public function handle(): PortfolioAvailabilityDTO
    {
        $profile = Profile::query()->first();

        if ($profile === null) {
            return new PortfolioAvailabilityDTO(hasHome: false, hasResume: false, hasOpenSource: false);
        }

        return new PortfolioAvailabilityDTO(
            hasHome: $profile->hasDetails() || in_array(true, $this->getContentStatus->handle(), true),
            hasResume: filled($profile->professional_summary) || Experience::query()->exists(),
            hasOpenSource: OpenSourceContribution::query()->exists(),
        );
    }
}
