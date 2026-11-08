<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\EndJobHuntAction;
use App\Enums\JobHuntOutcome;
use App\Http\Requests\JobHunt\EndJobHuntRequest;
use App\Models\JobHunt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;

final class JobHuntEndController
{
    /**
     * End a running hunt.
     */
    public function store(EndJobHuntRequest $request, JobHunt $jobHunt, EndJobHuntAction $action): RedirectResponse
    {
        abort_unless($jobHunt->isActive(), 409, 'This hunt has already ended.');

        $action->handle(
            $jobHunt,
            $request->enum('outcome', JobHuntOutcome::class) ?? JobHuntOutcome::Paused,
            Date::parse($request->string('ended_on')->toString()),
            $request->boolean('close_waiting_applications'),
        );

        toastSuccess('Hunt ended.');

        return back();
    }
}
