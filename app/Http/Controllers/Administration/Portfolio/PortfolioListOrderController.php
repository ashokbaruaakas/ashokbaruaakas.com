<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration\Portfolio;

use App\Actions\Portfolio\ReorderPortfolioItemsAction;
use App\Enums\PortfolioList;
use App\Http\Requests\Administration\Portfolio\PortfolioOrderRequest;
use Illuminate\Http\RedirectResponse;

final class PortfolioListOrderController
{
    /**
     * Save the order of a list.
     */
    public function update(PortfolioOrderRequest $request, PortfolioList $list, ReorderPortfolioItemsAction $action): RedirectResponse
    {
        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');

        $action->handle($list, $ids);

        return back();
    }
}
