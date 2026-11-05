<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Models\Profile;

final readonly class UpdateProfileAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Save the owner's profile, creating it on a fresh install.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Profile
    {
        $profile = Profile::query()->first() ?? new Profile();
        $profile->fill($attributes)->save();

        $this->scheduleRebuild->handle();

        return $profile;
    }
}
