<?php

declare(strict_types=1);

namespace App\Actions\Ats;

use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Models\AtsCheck;
use App\Models\Resume;

final readonly class RunAtsCheckAction
{
    public function __construct(
        private ResolveResumeAction $resolveResume,
        private BuildPlainTextResumeAction $plainTextResume,
        private ScoreResumeAction $scoreResume,
    ) {}

    /**
     * Score a resume, the public one unless another is given, against the keywords
     * of a job post when one is given, and save the result.
     *
     * @param  array{title: string|null, keywords: list<string>}|null  $job
     */
    public function handle(?array $job = null, ?Resume $resume = null): AtsCheck
    {
        $resume ??= Resume::public();
        $portfolio = $this->resolveResume->handle($resume);
        $result = $this->scoreResume->handle($portfolio, $this->plainTextResume->handle($portfolio), $job['keywords'] ?? null);

        return AtsCheck::query()->create([
            ...$result,
            'job_title' => $result['job_match_score'] === null ? null : ($job['title'] ?? 'Pasted job post'),
            'resume_name' => $resume->name,
        ]);
    }
}
