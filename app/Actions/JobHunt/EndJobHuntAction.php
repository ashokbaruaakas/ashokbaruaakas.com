<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Enums\JobApplicationStatus;
use App\Enums\JobHuntOutcome;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class EndJobHuntAction
{
    public function __construct(private ChangeJobApplicationStatusAction $changeStatus) {}

    /**
     * End a hunt, optionally closing the jobs still waiting on a reply as "No Response".
     */
    public function handle(JobHunt $hunt, JobHuntOutcome $outcome, CarbonInterface $endedOn, bool $closeWaitingApplications): void
    {
        DB::transaction(function () use ($hunt, $outcome, $endedOn, $closeWaitingApplications): void {
            $hunt->update(['ended_on' => $endedOn, 'outcome' => $outcome]);

            if (! $closeWaitingApplications) {
                return;
            }

            $hunt->applications()
                ->where('status', JobApplicationStatus::Applied)
                ->get()
                ->each(function (JobApplication $application) use ($endedOn): void {
                    $this->changeStatus->handle($application, JobApplicationStatus::NoResponse, $endedOn);
                });
        });
    }
}
