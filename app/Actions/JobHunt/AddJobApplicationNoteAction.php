<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Enums\JobApplicationEventType;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonInterface;

final readonly class AddJobApplicationNoteAction
{
    /**
     * Record something that happened on a job, such as a call being booked.
     */
    public function handle(JobApplication $application, string $body, CarbonInterface $occurredOn): JobApplicationEvent
    {
        return $application->events()->create([
            'type' => JobApplicationEventType::Note,
            'body' => $body,
            'occurred_on' => $occurredOn,
        ]);
    }
}
