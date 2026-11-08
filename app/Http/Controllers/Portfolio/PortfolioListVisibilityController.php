<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\UpdatePortfolioItemVisibilityAction;
use App\Enums\PortfolioList;
use App\Http\Requests\Portfolio\PortfolioItemVisibilityRequest;
use Illuminate\Http\RedirectResponse;

final class PortfolioListVisibilityController
{
    /**
     * Show or hide an item on the public pages.
     */
    public function update(PortfolioItemVisibilityRequest $request, PortfolioList $list, int $item, UpdatePortfolioItemVisibilityAction $action): RedirectResponse
    {
        abort_unless($list->canBeHidden(), 404);

        $action->handle($list->model()::query()->findOrFail($item), $request->boolean('is_visible'));

        return back();
    }
}
