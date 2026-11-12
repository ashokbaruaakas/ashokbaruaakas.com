<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\StartJobHuntAction;
use App\DTOs\PageMeta;
use App\Enums\JobApplicationStatus;
use App\Enums\JobChance;
use App\Enums\JobHuntOutcome;
use App\Enums\JobRelocation;
use App\Enums\WorkMode;
use App\Http\Requests\JobHunt\JobHuntRequest;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\Resume;
use App\Models\ResumeBuild;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class JobHuntController
{
    /**
     * List every hunt, the running one first, with a summary of each.
     */
    public function index(): Response
    {
        $hunts = JobHunt::query()
            ->withCount([
                'applications',
                'applications as applied_count' => fn (Builder $query) => $query->whereNotIn('status', [JobApplicationStatus::Wishlist, JobApplicationStatus::Ignored]),
                'applications as offers_count' => fn (Builder $query) => $query->whereIn('status', [JobApplicationStatus::Offer, JobApplicationStatus::Accepted, JobApplicationStatus::Declined]),
            ])
            ->orderByRaw('ended_on is null desc')
            ->latest('started_on')
            ->latest('id')
            ->get();

        return vue('job-hunt/Index', [
            'hunts' => $hunts,
            'workModes' => WorkMode::options(),
            'today' => today()->toDateString(),
        ], new PageMeta(
            title: 'Job Hunt',
        ));
    }

    /**
     * Start a new hunt.
     */
    public function store(JobHuntRequest $request, StartJobHuntAction $action): RedirectResponse
    {
        /** @var array{title: string, goal?: string|null, work_modes: list<string>, countries?: string|null, open_to_relocation: bool, notes?: string|null, started_on: string} $attributes */
        $attributes = $request->validated();

        $hunt = $action->handle($attributes);

        toastSuccess('Hunt started. Good luck!');

        return to_route('job-hunt.show', $hunt);
    }

    /**
     * Show a hunt and every job in it, with the resume tailored for each job.
     */
    public function show(JobHunt $jobHunt): Response
    {
        $applications = $jobHunt->applications()
            ->with('events')
            ->latest('updated_at')
            ->get()
            ->map(fn (JobApplication $application): array => [
                ...$application->toArray(),
                'needs_follow_up' => $application->needsFollowUp(),
            ]);

        return vue('job-hunt/Show', [
            'hunt' => [...$jobHunt->toArray(), 'is_active' => $jobHunt->isActive()],
            'applications' => $applications,
            'statuses' => JobApplicationStatus::choices(),
            'chances' => JobChance::options(),
            'workModes' => WorkMode::options(),
            'relocations' => JobRelocation::options(),
            'outcomes' => JobHuntOutcome::options(),
            'followUpAfterDays' => JobApplication::FOLLOW_UP_AFTER_DAYS,
            'today' => today()->toDateString(),
            'resumeBuilds' => $this->resumeBuildChoices($jobHunt),
            'tailoredResumes' => Resume::query()->active()
                ->whereIn('job_application_id', $applications->pluck('id'))
                ->latest('id')
                ->get(['id', 'name', 'job_application_id'])
                ->unique('job_application_id')
                ->map(fn (Resume $resume): array => ['id' => $resume->id, 'name' => $resume->name, 'jobApplicationId' => $resume->job_application_id])
                ->values()->all(),
            'publicResumeName' => Resume::query()->where('is_public', true)->value('name'),
        ], new PageMeta(
            title: $jobHunt->title,
        ));
    }

    /**
     * Update a hunt's title, what the owner is looking for and its notes.
     */
    public function update(JobHuntRequest $request, JobHunt $jobHunt): RedirectResponse
    {
        $jobHunt->update($request->validated());

        toastSuccess('Saved.');

        return back();
    }

    /**
     * Delete a hunt and every job in it.
     */
    public function destroy(JobHunt $jobHunt): RedirectResponse
    {
        $jobHunt->delete();

        toastSuccess('Hunt deleted.');

        return to_route('job-hunt.index');
    }

    /**
     * The PDFs a job can be marked as sent with: every resume's current one, and
     * the ones already sent with this hunt's jobs.
     *
     * @return list<array{id: int, label: string, downloadUrl: string}>
     */
    private function resumeBuildChoices(JobHunt $jobHunt): array
    {
        $current = Resume::query()->active()->get()->map(fn (Resume $resume): ?ResumeBuild => $resume->currentBuild())->filter();
        $sent = ResumeBuild::query()->with('resume')->whereIn('id', $jobHunt->applications()->whereNotNull('resume_build_id')->pluck('resume_build_id'))->get();

        return array_values($current->concat($sent)->unique('id')->sortByDesc('built_at')
            ->map(fn (ResumeBuild $build): array => [
                'id' => $build->id,
                'label' => "{$build->resume->name} · {$build->built_at?->format('j M Y')} · {$build->page_count} " . ($build->page_count === 1 ? 'page' : 'pages'),
                'downloadUrl' => route('resumes.builds.download', $build),
            ])->all());
    }
}
