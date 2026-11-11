<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeList: string
{
    use HasEnumMetadata;

    case Experience = 'experience';
    case Projects = 'projects';
    case Education = 'education';
    case Skills = 'skills';

    /**
     * Whether an item a resume has not been told about belongs on it. Featured
     * projects (tier 1) are shown on the home page, so only supporting projects join.
     */
    public function includesByDefault(?int $projectTier = null): bool
    {
        return $this !== self::Projects || $projectTier === 2;
    }

    /**
     * The list's portfolio items, in the order the owner sorted them.
     *
     * @return Builder<Model>
     */
    public function items(): Builder
    {
        return $this->portfolioList()->model()::query()->orderBy('sort_order')->orderBy('id');
    }

    public function table(): string
    {
        return $this->items()->getModel()->getTable();
    }

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
