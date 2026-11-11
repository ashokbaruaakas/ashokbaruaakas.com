<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Portfolio\BuildResumePdfAction;
use App\Enums\ResumePdfStatus;
use App\Models\ResumeBuild;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RebuildResumePdf implements ShouldQueue
{
    use Queueable;

    /** Must stay below the queue connection's retry_after. */
    public int $timeout = 80;

    /**
     * Without this, retries wait as long as the job's delay, because Laravel
     * prefers the delay over the worker's --backoff option.
     *
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(public readonly string $token) {}

    /**
     * Build the PDF unless a newer change has replaced this build.
     */
    public function handle(BuildResumePdfAction $buildResumePdf): void
    {
        $claimed = ResumeBuild::query()
            ->where('token', $this->token)
            ->whereIn('status', [ResumePdfStatus::Scheduled, ResumePdfStatus::Building])
            ->update(['status' => ResumePdfStatus::Building]);

        if ($claimed === 0) {
            return;
        }

        $buildResumePdf->handle(ResumeBuild::query()->where('token', $this->token)->sole());
    }

    /**
     * Mark this build as failed, keeping only the resume's latest failure.
     */
    public function failed(): void
    {
        $build = ResumeBuild::query()->where('token', $this->token)->first();

        if ($build === null) {
            return;
        }

        $build->update(['status' => ResumePdfStatus::Failed]);

        ResumeBuild::query()
            ->where('resume_id', $build->resume_id)
            ->where('status', ResumePdfStatus::Failed)
            ->whereKeyNot($build->id)
            ->delete();
    }
}
