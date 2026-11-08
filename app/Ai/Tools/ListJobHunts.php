<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\JobHunt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class ListJobHunts implements Tool
{
    public function description(): string
    {
        return 'List every job hunt, the running one first, with how many jobs each has in every status.';
    }

    public function handle(Request $request): string
    {
        $hunts = JobHunt::query()
            ->with('applications:id,job_hunt_id,status')
            ->orderByRaw('ended_on is null desc')
            ->latest('started_on')
            ->get()
            ->map(fn (JobHunt $hunt): array => [
                'id' => $hunt->id,
                'title' => $hunt->title,
                'goal' => $hunt->goal,
                'started_on' => $hunt->started_on->toDateString(),
                'ended_on' => $hunt->ended_on?->toDateString(),
                'outcome' => $hunt->outcome?->label(),
                'is_active' => $hunt->isActive(),
                'jobs_by_status' => $hunt->applications
                    ->countBy(fn ($application): string => $application->status->label())
                    ->all(),
            ]);

        return $hunts->isEmpty() ? 'There are no job hunts yet.' : $hunts->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
