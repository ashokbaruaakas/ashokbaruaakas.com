<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\PortfolioList;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class SavePortfolioItemAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Create or update an item in the list. New items go to the end of the list.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(PortfolioList $list, array $attributes, ?Model $item = null): Model
    {
        $savedItem = DB::transaction(function () use ($list, $attributes, $item): Model {
            /** @var array<array-key, array<string, mixed>> $links */
            $links = $attributes['links'] ?? [];
            unset($attributes['links']);

            $model = $list->model();
            $lastSortOrder = $model::query()->max('sort_order');
            $item ??= new $model(['sort_order' => (is_numeric($lastSortOrder) ? (int) $lastSortOrder : -1) + 1]);
            $item->fill($attributes)->save();

            if ($item instanceof Project) {
                $this->syncLinks($item, $links);
            }

            return $item;
        });

        $this->scheduleRebuild->handle();

        return $savedItem;
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $links
     */
    private function syncLinks(Project $project, array $links): void
    {
        $project->links()->delete();

        foreach (array_values($links) as $sortOrder => $link) {
            $project->links()->create([...$link, 'sort_order' => $sortOrder]);
        }
    }
}
