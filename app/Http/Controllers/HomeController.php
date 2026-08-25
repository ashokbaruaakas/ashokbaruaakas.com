<?php

namespace App\Http\Controllers;

use App\Actions\Portfolio\GetPortfolioData;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the portfolio home page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'portfolio' => fn () => app(GetPortfolioData::class)->handle(),
        ]);
    }
}
