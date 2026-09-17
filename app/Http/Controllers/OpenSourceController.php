<?php

namespace App\Http\Controllers;

use App\Actions\Portfolio\GetPortfolioData;
use Inertia\Inertia;
use Inertia\Response;

class OpenSourceController extends Controller
{
    /**
     * Show the open-source work page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('open-source/Index', [
            'portfolio' => fn () => app(GetPortfolioData::class)->handle(),
        ]);
    }
}
