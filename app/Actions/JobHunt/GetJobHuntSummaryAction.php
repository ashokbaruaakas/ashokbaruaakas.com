<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Enums\JobApplicationStage;
use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetJobHuntSummaryAction
{
    private const int UPCOMING_LIMIT = 4;

    /**
     * Summarize the running hunt for the dashboard: jobs per stage, what needs a follow-up,
     * and the next steps and deadlines still ahead. Null while no hunt is running.
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     started_on: string,
     *     stages: array<string, int>,
     *     interviewing: int,
     *     offers: int,
     *     needs_follow_up: int,
     *     upcoming: list<array{id: int, company: string, role: string, label: string, date: string}>
     * }|null
     */
    public function handle(): ?array
    {
        $hunt = JobHunt::query()->active()->latest('started_on')->latest('id')->first();

        if ($hunt === null) {
            return null;
        }

        $applications = $hunt->applications()->with('events')->get();
        $stages = array_fill_keys(array_column(JobApplicationStage::cases(), 'value'), 0);

        foreach ($applications as $application) {
            $stages[$application->status->stage()->value]++;
        }

        return [
            'id' => $hunt->id,
            'title' => $hunt->title,
            'started_on' => $hunt->started_on->toDateString(),
            'stages' => $stages,
            'interviewing' => $applications->where('status', JobApplicationStatus::Interviewing)->count(),
            'offers' => $applications->where('status', JobApplicationStatus::Offer)->count(),
            'needs_follow_up' => $applications->filter(fn (JobApplication $application): bool => $application->needsFollowUp())->count(),
            'upcoming' => $this->upcoming($applications),
        ];
    }

    /**
     * The soonest next steps and deadlines from today on, for jobs that are still open.
     *
     * @param  Collection<int, JobApplication>  $applications
     * @return list<array{id: int, company: string, role: string, label: string, date: string}>
     */
    private function upcoming(Collection $applications): array
    {
        $today = today();
        $dates = [];

        foreach ($applications as $application) {
            if ($application->status->stage() === JobApplicationStage::Closed) {
                continue;
            }

            $candidates = [
                [$application->next_step ?? 'Next step', $application->next_step_on],
                ['Deadline', $application->deadline],
            ];

            foreach ($candidates as [$label, $date]) {
                if ($date instanceof CarbonInterface && $date->gte($today)) {
                    $dates[] = [
                        'id' => $application->id,
                        'company' => $application->company,
                        'role' => $application->role,
                        'label' => $label,
                        'date' => $date->toDateString(),
                    ];
                }
            }
        }

        usort($dates, fn (array $first, array $second): int => $first['date'] <=> $second['date']);

        return array_slice($dates, 0, self::UPCOMING_LIMIT);
    }
}
