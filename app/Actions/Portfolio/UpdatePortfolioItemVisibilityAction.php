<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use Illuminate\Database\Eloquent\Model;

final readonly class UpdatePortfolioItemVisibilityAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Show or hide an item on the public pages.
     */
    public function handle(Model $item, bool $isVisible): void
    {
        $item->forceFill(['is_visible' => $isVisible])->save();

        $this->scheduleRebuild->handle();
    }
}
