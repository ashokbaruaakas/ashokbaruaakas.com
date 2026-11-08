<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Models\JobHunt;
use Illuminate\Validation\ValidationException;

final readonly class StartJobHuntAction
{
    /**
     * Start a hunt. Only one hunt runs at a time, so the current one has to end first.
     *
     * @param  array{title: string, goal?: string|null, notes?: string|null, started_on: string}  $attributes
     *
     * @throws ValidationException When a hunt is already running.
     */
    public function handle(array $attributes): JobHunt
    {
        if (JobHunt::query()->active()->exists()) {
            throw ValidationException::withMessages(['title' => 'End your current hunt before starting a new one.']);
        }

        return JobHunt::query()->create($attributes);
    }
}
