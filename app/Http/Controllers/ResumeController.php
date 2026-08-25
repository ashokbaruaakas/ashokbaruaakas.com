<?php

namespace App\Http\Controllers;

use App\Actions\Portfolio\GetPortfolioData;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    /**
     * Show the resume page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('resume/Index', [
            'portfolio' => fn () => app(GetPortfolioData::class)->handle(),
        ]);
    }
}
