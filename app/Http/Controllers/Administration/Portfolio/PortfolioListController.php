<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration\Portfolio;

use App\Actions\Portfolio\SavePortfolioItemAction;
use App\DTOs\PageMeta;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\PortfolioList;
use App\Http\Requests\Administration\Portfolio\PortfolioItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class PortfolioListController
{
    /**
     * Show the items in a list.
     */
    public function index(PortfolioList $list): Response
    {
        $query = $list->model()::query()->orderBy('sort_order');

        if ($list === PortfolioList::Projects) {
            $query->with('links');
        }

        return vue('administration/portfolio/List', [
            'list' => [
                'key' => $list->value,
                'title' => $list->title(),
                'description' => $list->description(),
                'titleField' => $list->titleField(),
                'subtitleField' => $list->subtitleField(),
            ],
            'fields' => array_map(fn (PortfolioFieldDTO $field): array => $field->toArray(), $list->fields()),
            'items' => $query->get(),
        ], new PageMeta(
            title: $list->title(),
        ));
    }

    /**
     * Add an item to a list.
     */
    public function store(PortfolioItemRequest $request, PortfolioList $list, SavePortfolioItemAction $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        $action->handle($list, $attributes);

        toastSuccess('Added.');

        return back();
    }

    /**
     * Update an item in a list.
     */
    public function update(PortfolioItemRequest $request, PortfolioList $list, int $item, SavePortfolioItemAction $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        $action->handle($list, $attributes, $this->findItem($list, $item));

        toastSuccess('Saved.');

        return back();
    }

    /**
     * Remove an item from a list.
     */
    public function destroy(PortfolioList $list, int $item): RedirectResponse
    {
        $this->findItem($list, $item)->delete();

        toastSuccess('Removed.');

        return back();
    }

    private function findItem(PortfolioList $list, int $item): Model
    {
        return $list->model()::query()->findOrFail($item);
    }
}
