<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\ResolveResumeAction;
use App\DTOs\Portfolio\PortfolioDTO;
use App\Models\Resume;
use Inertia\Response;

final readonly class ResumeController
{
    public function __construct(private ResolveResumeAction $resolveResume) {}

    /**
     * Show the public resume.
     */
    public function show(): Response
    {
        $resume = Resume::public();

        return vue('resume/Index', [
            'portfolio' => fn (): PortfolioDTO => $this->resolveResume->handle($resume),
            'template' => $resume->template->value,
        ]);
    }
}
