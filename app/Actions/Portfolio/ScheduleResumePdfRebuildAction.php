<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumePdfStatus;
use App\Jobs\RebuildResumePdf;
use App\Models\ResumePdfBuild;
use Illuminate\Support\Str;

final readonly class ScheduleResumePdfRebuildAction
{
    private const int DELAY_MINUTES = 5;

    /**
     * Schedule a rebuild, replacing any scheduled one. A job only runs if it
     * still holds the latest token, so each change restarts the wait.
     */
    public function handle(bool $immediately = false): void
    {
        $token = (string) Str::ulid();
        $scheduledFor = $immediately ? now() : now()->addMinutes(self::DELAY_MINUTES);

        ResumePdfBuild::query()->updateOrCreate(['id' => 1], [
            'token' => $token,
            'status' => ResumePdfStatus::Scheduled,
            'scheduled_for' => $scheduledFor,
        ]);

        RebuildResumePdf::dispatch($token)->delay($scheduledFor);
    }
}
