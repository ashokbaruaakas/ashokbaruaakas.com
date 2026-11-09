<?php

declare(strict_types=1);

namespace App\Actions\Ats;

use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\GetPortfolioDataAction;
use App\Models\AtsCheck;

final readonly class RunAtsCheckAction
{
    public function __construct(
        private GetPortfolioDataAction $portfolioData,
        private BuildPlainTextResumeAction $plainTextResume,
        private ScoreResumeAction $scoreResume,
    ) {}

    /**
     * Score the resume, against the keywords of a job post when one is given, and save the result.
     *
     * @param  array{title: string|null, keywords: list<string>}|null  $job
     */
    public function handle(?array $job = null): AtsCheck
    {
        $portfolio = $this->portfolioData->handle();
        $result = $this->scoreResume->handle($portfolio, $this->plainTextResume->handle($portfolio), $job['keywords'] ?? null);

        return AtsCheck::query()->create([
            ...$result,
            'job_title' => $result['job_match_score'] === null ? null : ($job['title'] ?? 'Pasted job post'),
        ]);
    }
}
