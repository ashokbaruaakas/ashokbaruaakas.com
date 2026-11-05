<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\PortfolioList;
use Illuminate\Support\Facades\DB;

final readonly class ReorderPortfolioItemsAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Save the list's order. Items that aren't in the given order keep their position.
     *
     * @param  array<int, int>  $ids
     */
    public function handle(PortfolioList $list, array $ids): void
    {
        DB::transaction(function () use ($list, $ids): void {
            foreach ($ids as $sortOrder => $id) {
                $list->model()::query()->whereKey($id)->update(['sort_order' => $sortOrder]);
            }
        });

        $this->scheduleRebuild->handle();
    }
}
