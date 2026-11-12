<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Assistant\GetConversationDetailsAction;
use App\Actions\Portfolio\GetResumeEditorDataAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Actions\Portfolio\SaveResumeAction;
use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\Ai\Conversations;
use App\DTOs\PageMeta;
use App\Enums\ResumePdfStatus;
use App\Http\Requests\Portfolio\ResumeStoreRequest;
use App\Http\Requests\Portfolio\ResumeUpdateRequest;
use App\Models\AssistantConversation;
use App\Models\Resume;
use App\Settings\AiSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

/**
 * @phpstan-import-type ResumeData from SaveResumeAction
 */
final class ResumeSettingsController
{
    /**
     * List the resumes.
     */
    public function index(): Response
    {
        return vue('resumes/Index', [
            'resumes' => Resume::query()->with('jobApplication')->orderByDesc('is_public')->orderBy('archived_at')->orderBy('name')->get()
                ->map(fn (Resume $resume): array => [
                    'id' => $resume->id,
                    'name' => $resume->name,
                    'template' => $resume->template->label(),
                    'pageCount' => $resume->page_count,
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
        return to_route('resumes.edit', Resume::public());
    }

    /**
     * Start a resume.
     */
    public function store(ResumeStoreRequest $request, SaveResumeAction $action): RedirectResponse
    {
        $duplicateOf = $request->filled('duplicate_of') ? Resume::query()->findOrFail($request->integer('duplicate_of')) : null;
        $resume = $action->create($request->string('name')->toString(), $duplicateOf, $request->filled('job_application_id') ? $request->integer('job_application_id') : null);

        toastSuccess('Resume created.');

        return to_route('resumes.edit', $resume);
    }

    /**
     * Show a resume's settings, selection, overrides and build history, and
     * the assistant conversations held in its editor. The assistant panel asks
     * for one, given as the `conversation` query, with a partial reload.
     */
    public function edit(Request $request, Resume $resume, GetResumeEditorDataAction $getEditorData, GetConversationDetailsAction $getConversationDetails, AiSettings $settings): Response
    {
        return vue('resumes/Edit', [
            ...$getEditorData->handle($resume),
            'assistant' => [
                'isReady' => $settings->hasApiKey(),
                'provider' => $settings->provider->label(),
                'model' => $settings->chatModel(),
                'conversations' => Conversations::of(authUser())
                    ->where('resume_id', $resume->id)
                    ->latest('updated_at')
                    ->limit(30)
                    ->get(['id', 'title', 'updated_at'])
                    ->map(fn (AssistantConversation $conversation): array => [
                        'id' => $conversation->id,
                        'title' => $conversation->title,
                        'updated_at' => $conversation->updated_at?->toIso8601String(),
                    ])->all(),
            ],
            'opensAssistant' => $request->boolean('assistant'),
            'assistantConversation' => optionalProp(fn (): ?array => ($conversation = $request->filled('conversation')
                ? Conversations::of(authUser())->find($request->string('conversation')->toString())
                : null) instanceof AssistantConversation ? $getConversationDetails->handle($conversation) : null),
        ], new PageMeta(
            title: $resume->name,
        ));
    }

    /**
     * Save a resume.
     */
    public function update(ResumeUpdateRequest $request, Resume $resume, SaveResumeAction $action): RedirectResponse
    {
        /** @var ResumeData $data */
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
            $resume->update(['is_public' => true, 'archived_at' => null, 'published_at' => now()]);
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

        if ($resume->hasSentBuilds()) {
            $resume->update(['archived_at' => now()]);
            $resume->builds()->whereNot('status', ResumePdfStatus::Ready)->delete();

            toastSuccess('Resume archived. The PDFs sent with job applications are kept.');

            return to_route('resumes.index');
        }

        $resume->deleteWithPdfs();

        toastSuccess('Resume deleted.');

        return to_route('resumes.index');
    }

    /**
     * The resume as the editor's unsaved changes would render it, for the live preview.
     */
    public function preview(ResumeUpdateRequest $request, Resume $resume, SaveResumeAction $action, ResolveResumeAction $resolveResume): JsonResponse
    {
        /** @var ResumeData $data */
        $data = $request->validated();
        $draft = $action->draft($resume, $data);

        return response()->json([
            'portfolio' => $resolveResume->handle($draft)->toArray(),
            'design' => $draft->design(),
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
