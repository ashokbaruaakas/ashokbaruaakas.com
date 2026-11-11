<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\GetPortfolioDataAction;
use App\Models\Resume;
use Inertia\Response;

final readonly class ResumeController
{
    public function __construct(private GetPortfolioDataAction $getPortfolioData) {}

    /**
     * Show the public resume.
     */
    public function show(): Response
    {
        return vue('resume/Index', [
            'portfolio' => $this->getPortfolioData->handle(...),
            'template' => fn (): string => Resume::public()->template->value,
        ]);
    }
}
