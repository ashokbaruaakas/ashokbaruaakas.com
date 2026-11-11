<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\ResolveResumeAction;
use App\DTOs\Portfolio\PortfolioDTO;
use App\Models\Resume;
use Inertia\Response;

final readonly class ResumePrintController
{
    public function __construct(private ResolveResumeAction $resolveResume) {}

    /**
     * Render a resume's template alone for headless Chrome to print. Reached
     * only through a short-lived signed link, as private resumes have no other
     * public address.
     */
    public function show(Resume $resume): Response
    {
        return vue('resume/Print', [
            'portfolio' => fn (): PortfolioDTO => $this->resolveResume->handle($resume),
            'template' => $resume->template->value,
        ]);
    }
}
