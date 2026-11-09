<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class ListJobApplications implements Tool
{
    public function description(): string
    {
        return 'List the jobs in a hunt with their status, chance, dates and next step. Without a hunt id it uses the running hunt, or the latest one.';
    }

    public function handle(Request $request): string
    {
        $huntId = $request->filled('hunt_id') ? $request->integer('hunt_id') : null;

        $hunt = $huntId !== null
            ? JobHunt::query()->whereKey($huntId)->first()
            : JobHunt::query()->orderByRaw('ended_on is null desc')->latest('started_on')->first();

        if ($hunt === null) {
            return $huntId !== null ? "There is no job hunt with id {$huntId}." : 'There are no job hunts yet.';
        }

        $statuses = array_values(array_filter(array_map(
            fn (mixed $status): ?JobApplicationStatus => is_string($status) ? JobApplicationStatus::tryFrom($status) : null,
            $request->array('statuses'),
        )));

        $applications = $hunt->applications()
            ->with('events')
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->latest('updated_at')
            ->get()
            ->map(fn (JobApplication $application): array => [
                'id' => $application->id,
                'company' => $application->company,
                'role' => $application->role,
                'status' => $application->status->label(),
                'chance' => $application->chance?->label(),
                'country' => $application->country,
                'city' => $application->location,
                'work_mode' => $application->work_mode?->label(),
                'relocation' => $application->relocation?->label() ?? 'Not mentioned',
                'applied_on' => $application->applied_on?->toDateString(),
                'deadline' => $application->deadline?->toDateString(),
                'next_step' => $application->next_step,
                'next_step_on' => $application->next_step_on?->toDateString(),
                'needs_follow_up' => $application->needsFollowUp(),
                'updated_at' => $application->updated_at?->toDateString(),
            ]);

        if ($request->boolean('needs_follow_up')) {
            $applications = $applications->where('needs_follow_up', true)->values();
        }

        return json_encode([
            'hunt' => ['id' => $hunt->id, 'title' => $hunt->title, 'is_active' => $hunt->isActive()],
            'follow_up_after_days' => JobApplication::FOLLOW_UP_AFTER_DAYS,
            'jobs' => $applications->all(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'hunt_id' => $schema->integer()->description('The hunt to list. Leave out for the running or latest hunt.'),
            'statuses' => $schema->array()
                ->items($schema->string()->enum(array_column(JobApplicationStatus::cases(), 'value')))
                ->description('Only include jobs in these statuses.'),
            'needs_follow_up' => $schema->boolean()->description('Only include applied jobs with no activity for a while.'),
        ];
    }
}
