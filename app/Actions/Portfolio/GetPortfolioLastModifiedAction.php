<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\PortfolioList;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\ResumeBuild;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class GetPortfolioLastModifiedAction
{
    /**
     * Find when the public portfolio content last changed. Every edit schedules a resume PDF
     * rebuild, so that record also covers deletions, which leave no row behind to date.
     */
    public function handle(): ?CarbonInterface
    {
        $models = [
            Profile::class,
            PortfolioSection::class,
            PageContent::class,
            ResumeBuild::class,
            ...array_map(fn (PortfolioList $list): string => $list->model(), PortfolioList::cases()),
        ];

        $latest = collect($models)
            ->map(fn (string $model): mixed => $model::query()->max('updated_at'))
            ->filter()
            ->max();

        return is_string($latest) ? Date::parse($latest) : null;
    }
}
