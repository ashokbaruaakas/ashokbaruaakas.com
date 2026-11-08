<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class ShowJobApplication implements Tool
{
    public function description(): string
    {
        return 'Show everything about one job: its details, notes and timeline of status changes and notes.';
    }

    public function handle(Request $request): string
    {
        $application = JobApplication::query()->with(['events', 'hunt'])->whereKey($request->integer('id'))->first();

        if ($application === null) {
            return 'There is no job with that id.';
        }

        return json_encode([
            'id' => $application->id,
            'hunt' => $application->hunt->title,
            'company' => $application->company,
            'role' => $application->role,
            'status' => $application->status->label(),
            'chance' => $application->chance?->label(),
            'chance_note' => $application->chance_note,
            'link' => $application->link,
            'country' => $application->country,
            'location' => $application->location,
            'source' => $application->source,
            'salary' => $application->salary,
            'contact' => $application->contact,
            'applied_on' => $application->applied_on?->toDateString(),
            'deadline' => $application->deadline?->toDateString(),
            'next_step' => $application->next_step,
            'next_step_on' => $application->next_step_on?->toDateString(),
            'needs_follow_up' => $application->needsFollowUp(),
            'notes' => $application->notes,
            'timeline' => $application->events->map(fn (JobApplicationEvent $event): array => [
                'on' => $event->occurred_on->toDateString(),
                'type' => $event->type->value,
                'from_status' => $event->from_status?->label(),
                'to_status' => $event->to_status?->label(),
                'note' => $event->body,
            ])->all(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The job id, from the job list.')->required(),
        ];
    }
}
