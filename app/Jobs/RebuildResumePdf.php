<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Portfolio\BuildResumePdfAction;
use App\Enums\ResumePdfStatus;
use App\Models\ResumePdfBuild;
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
     * Build the PDF unless a newer change has replaced this rebuild.
     */
    public function handle(BuildResumePdfAction $buildResumePdf): void
    {
        if (! $this->markAs(ResumePdfStatus::Building)) {
            return;
        }

        $buildResumePdf->handle($this->token);

        $this->markAs(ResumePdfStatus::Ready);
    }

    /**
     * Handle a job that has run out of attempts.
     */
    public function failed(): void
    {
        $this->markAs(ResumePdfStatus::Failed);
    }

    private function markAs(ResumePdfStatus $status): bool
    {
        return ResumePdfBuild::query()->where('token', $this->token)->update(['status' => $status]) > 0;
    }
}
