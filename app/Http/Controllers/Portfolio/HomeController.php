<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\GetPortfolioDataAction;
use Inertia\Response;

final readonly class HomeController
{
    public function __construct(private GetPortfolioDataAction $getPortfolioData) {}

    /**
     * Show the portfolio home page.
     */
    public function show(): Response
    {
        return vue('Home', [
            'portfolio' => $this->getPortfolioData->handle(...),
        ]);
    }
}
