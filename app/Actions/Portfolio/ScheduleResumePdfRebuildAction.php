<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumePdfStatus;
use App\Jobs\RebuildResumePdf;
use App\Models\Resume;
use Illuminate\Support\Str;

final readonly class ScheduleResumePdfRebuildAction
{
    private const int DELAY_MINUTES = 5;

    /**
     * Schedule a build of one resume, or of every resume, unless it is archived,
     * replacing any build still waiting to run, so each change restarts the wait.
     */
    public function handle(bool $immediately = false, ?Resume $resume = null): void
    {
        $resumes = $resume instanceof Resume ? [$resume] : Resume::query()->active()->get()->all();
        $scheduledFor = $immediately ? now() : now()->addMinutes(self::DELAY_MINUTES);

        foreach ($resumes as $scheduled) {
            if ($scheduled->archived_at !== null) {
                continue;
            }

            $scheduled->builds()->where('status', ResumePdfStatus::Scheduled)->delete();

            $build = $scheduled->builds()->create([
                'token' => (string) Str::ulid(),
                'status' => ResumePdfStatus::Scheduled,
                'scheduled_for' => $scheduledFor,
            ]);

            RebuildResumePdf::dispatch($build->token)->delay($scheduledFor);
        }
    }
}
