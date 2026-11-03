<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Models\Profile;

final readonly class UpdateProfileAction
{
    /**
     * Save the owner's profile, creating it on a fresh install.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Profile
    {
        $profile = Profile::query()->first() ?? new Profile();
        $profile->fill($attributes)->save();

        return $profile;
    }
}
