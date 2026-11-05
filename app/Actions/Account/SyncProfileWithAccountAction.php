<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\Models\Profile;
use App\Models\User;

final readonly class SyncProfileWithAccountAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Carry the account's new details over to the public profile, leaving any
     * value the owner has already made different on the profile alone.
     *
     * @param  array<string, mixed>  $previousDetails
     */
    public function handle(User $user, array $previousDetails): void
    {
        $updatedRows = 0;

        foreach ($previousDetails as $field => $previousValue) {
            $updatedRows += Profile::query()->where($field, $previousValue)->update([$field => $user->{$field}]);
        }

        if ($updatedRows > 0) {
            $this->scheduleRebuild->handle();
        }
    }
}
