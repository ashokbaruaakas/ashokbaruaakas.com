<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\MeasureResumeAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Actions\Portfolio\SaveResumeAction;
use App\DTOs\Portfolio\PortfolioDTO;
use App\Models\Resume;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;

/**
 * @phpstan-import-type ResumeData from SaveResumeAction
 */
final readonly class ResumeMeasureController
{
    public function __construct(
        private SaveResumeAction $saveResume,
        private ResolveResumeAction $resolveResume,
    ) {}

    /**
     * Lay out a draft of a resume on A4 pages for headless Chrome to measure.
     * Reached only through a short-lived signed link that names the draft.
     */
    public function show(Resume $resume, string $token): Response
    {
        /** @var ResumeData|null $data */
        $data = Cache::get(MeasureResumeAction::cacheKey($token));

        abort_if($data === null, 404);

        $draft = $this->saveResume->draft($resume, $data);

        return vue('resume/Measure', [
            'portfolio' => fn (): PortfolioDTO => $this->resolveResume->handle($draft),
            'design' => $draft->design(),
        ]);
    }
}
