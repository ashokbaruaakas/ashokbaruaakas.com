<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeList: string
{
    use HasEnumMetadata;

    case Experience = 'experience';
    case Projects = 'projects';
    case Education = 'education';
    case Skills = 'skills';

    public function portfolioList(): PortfolioList
    {
        return match ($this) {
            self::Experience => PortfolioList::Experience,
            self::Projects => PortfolioList::Projects,
            self::Education => PortfolioList::Education,
            self::Skills => PortfolioList::Skills,
        };
    }
}
