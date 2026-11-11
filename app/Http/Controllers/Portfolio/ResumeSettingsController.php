<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\GetResumeEditorDataAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Actions\Portfolio\SaveResumeAction;
use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\DTOs\PageMeta;
use App\DTOs\Portfolio\PortfolioDTO;
use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use App\Http\Requests\Portfolio\ResumeStoreRequest;
use App\Http\Requests\Portfolio\ResumeUpdateRequest;
use App\Models\Resume;
use App\Models\ResumeBuild;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

final class ResumeSettingsController
{
    /**
     * List the resumes.
     */
    public function index(): Response
    {
        return vue('portfolio/Resumes', [
            'resumes' => Resume::query()->with('jobApplication')->orderByDesc('is_public')->orderBy('archived_at')->orderBy('name')->get()
                ->map(fn (Resume $resume): array => [
                    'id' => $resume->id,
                    'name' => $resume->name,
                    'template' => $resume->template->label(),
                    'pageCount' => $resume->page_count,
                    'pageLimit' => $resume->page_limit,
                    'isOverPageLimit' => $resume->isOverPageLimit(),
                    'isPublic' => $resume->is_public,
                    'isArchived' => $resume->archived_at !== null,
                    'tailoredFor' => $resume->jobApplication === null ? null : "{$resume->jobApplication->company} — {$resume->jobApplication->role}",
                    'builtAt' => $resume->currentBuild()?->built_at?->toIso8601String(),
                ])->all(),
        ], new PageMeta(
            title: 'Resumes',
        ));
    }

    /**
     * Open the public resume's settings.
     */
    public function editPublic(): RedirectResponse
    {
        return to_route('portfolio.resumes.edit', Resume::public());
    }

    /**
     * Start a resume.
     */
    public function store(ResumeStoreRequest $request, SaveResumeAction $action): RedirectResponse
    {
        $duplicateOf = $request->filled('duplicate_of') ? Resume::query()->findOrFail($request->integer('duplicate_of')) : null;
        $resume = $action->create($request->string('name')->toString(), $duplicateOf, $request->filled('job_application_id') ? $request->integer('job_application_id') : null);

        toastSuccess('Resume created.');

        return to_route('portfolio.resumes.edit', $resume);
    }

    /**
     * Show a resume's settings, selection, overrides and build history.
     */
    public function edit(Resume $resume, GetResumeEditorDataAction $getEditorData): Response
    {
        return vue('portfolio/Resume', $getEditorData->handle($resume), new PageMeta(
            title: $resume->name,
        ));
    }

    /**
     * Save a resume.
     */
    public function update(ResumeUpdateRequest $request, Resume $resume, SaveResumeAction $action): RedirectResponse
    {
        /** @var array{name: string, template: string, page_limit: int, job_application_id?: int|null, selection?: array<string, list<array{id: int, included: bool}>>, overrides?: array{headline?: string|null, summary?: string|null, highlights?: array<int|string, list<string>|null>}, reviewed_overrides?: list<string>} $data */
        $data = $request->validated();

        $action->update($resume, $data);

        toastSuccess('Resume saved.');

        return back();
    }

    /**
     * Make a resume the one visitors see, and build its PDF right away.
     */
    public function publish(Resume $resume, ScheduleResumePdfRebuildAction $scheduleRebuild): RedirectResponse
    {
        DB::transaction(function () use ($resume): void {
            Resume::query()->where('is_public', true)->update(['is_public' => false]);
            $resume->update(['is_public' => true, 'archived_at' => null]);
        });

        $scheduleRebuild->handle(immediately: true, resume: $resume);

        toastSuccess("{$resume->name} is now your public resume.");

        return back();
    }

    /**
     * Delete a resume, or archive it when a job application keeps one of its PDFs.
     */
    public function destroy(Resume $resume): RedirectResponse
    {
        if ($resume->is_public) {
            toastError('Make another resume public before you remove this one.');

            return back();
        }

        if ($resume->builds()->whereHas('sentWith')->exists()) {
            $resume->update(['archived_at' => now()]);
            $resume->builds()->whereNot('status', ResumePdfStatus::Ready)->delete();

            toastSuccess('Resume archived. The PDFs sent with job applications are kept.');

            return to_route('portfolio.resumes.index');
        }

        $paths = $resume->builds()->whereNotNull('path')->pluck('path')->all();
        $resume->delete();
        Storage::disk(ResumeBuild::DISK)->delete($paths);

        toastSuccess('Resume deleted.');

        return to_route('portfolio.resumes.index');
    }

    /**
     * Render a resume with a template, for the template picker.
     */
    public function preview(Resume $resume, ResumeTemplate $template, ResolveResumeAction $resolveResume): Response
    {
        return vue('resume/Preview', [
            'portfolio' => fn (): PortfolioDTO => $resolveResume->handle($resume),
            'template' => $template->value,
        ]);
    }

    /**
     * Rebuild a resume's PDF without waiting.
     */
    public function rebuild(Resume $resume, ScheduleResumePdfRebuildAction $scheduleRebuild): RedirectResponse
    {
        $scheduleRebuild->handle(immediately: true, resume: $resume);

        return back();
    }
}
