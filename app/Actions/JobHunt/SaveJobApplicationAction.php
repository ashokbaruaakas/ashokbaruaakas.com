<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Support\Facades\DB;

final readonly class SaveJobApplicationAction
{
    public function __construct(private RecordSentResumeAction $recordSentResume) {}

    /**
     * Add a job to a hunt and start its timeline. A job added in an applied status gets today as its applied date unless one is given.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(JobHunt $hunt, JobApplicationStatus $status, array $attributes): JobApplication
    {
        $details = $this->details($attributes);

        if ($status->hasApplied()) {
            $details['applied_on'] ??= today();
        }

        return DB::transaction(function () use ($hunt, $status, $details): JobApplication {
            $application = $hunt->applications()->create([...$details, 'status' => $status]);

            $application->events()->create([
                'type' => JobApplicationEventType::StatusChange,
                'to_status' => $status,
                'occurred_on' => $application->applied_on ?? today(),
            ]);

            $this->recordSentResume->handle($application);

            return $application;
        });
    }

    /**
     * Update a job's details. Its status changes through ChangeJobApplicationStatusAction instead.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(JobApplication $application, array $attributes): JobApplication
    {
        $application->update($this->details($attributes));

        return $application;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function details(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip(JobApplication::EDITABLE_ATTRIBUTES));
    }
}
