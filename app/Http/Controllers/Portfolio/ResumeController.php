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
     * Show the public resume's content as a web page.
     */
    public function show(): Response
    {
        return vue('resume/Index', [
            'portfolio' => fn (): PortfolioDTO => $this->resolveResume->handle(Resume::public()),
        ]);
    }
}
