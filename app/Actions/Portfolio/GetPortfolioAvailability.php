<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\PortfolioAvailabilityDTO;
use App\Models\Experience;
use App\Models\OpenSourceContribution;
use App\Models\Profile;

class GetPortfolioAvailability
{
    /**
     * Determine which public portfolio pages have enough data to be shown.
     */
    public function handle(): PortfolioAvailabilityDTO
    {
        $profile = Profile::query()->first(['professional_summary']);

        if ($profile === null) {
            return new PortfolioAvailabilityDTO(hasProfile: false, hasResume: false, hasOpenSource: false);
        }

        return new PortfolioAvailabilityDTO(
            hasProfile: true,
            hasResume: filled($profile->professional_summary) || Experience::query()->exists(),
            hasOpenSource: OpenSourceContribution::query()->exists(),
        );
    }
}
