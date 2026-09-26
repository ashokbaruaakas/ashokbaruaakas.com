<?php

namespace App\Http\Controllers;

use App\Actions\Portfolio\GetPortfolioData;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function __construct(private GetPortfolioData $getPortfolioData) {}

    /**
     * Show the resume page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('resume/Index', [
            'portfolio' => fn () => $this->getPortfolioData->handle(),
        ]);
    }
}
