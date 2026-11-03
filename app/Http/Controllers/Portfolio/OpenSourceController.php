<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\GetPortfolioDataAction;
use Inertia\Response;

final readonly class OpenSourceController
{
    public function __construct(private GetPortfolioDataAction $getPortfolioData) {}

    /**
     * Show the open-source work page.
     */
    public function show(): Response
    {
        return vue('open-source/Index', [
            'portfolio' => $this->getPortfolioData->handle(...),
        ]);
    }
}
