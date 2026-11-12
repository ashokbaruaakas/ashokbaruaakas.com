<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Actions\Assistant\GetConversationDetailsAction;
use App\Ai\Conversations;
use App\Ai\PortfolioSection;
use App\DTOs\PageMeta;
use App\Http\Requests\Assistant\AssistantConversationRequest;
use App\Models\AssistantConversation;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Settings\AiSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class AssistantController
{
    /**
     * Show the assistant with the owner's recent conversations, and the messages of the open one.
     * A new conversation can start about a job, given as the `job` query, and with a portfolio item
     * mentioned in the message box, given as the `mention` query with an optional `field` label.
     * The portfolio items to mention include every resume that is not archived.
     */
    public function show(Request $request, AiSettings $settings, GetConversationDetailsAction $getConversationDetails, ?string $conversation = null): Response
    {
        $user = authUser();
        $active = $conversation !== null ? Conversations::of($user)->with('job.hunt')->findOrFail($conversation) : null;
        $newJob = $active === null && $request->filled('job')
            ? JobApplication::query()->with('hunt')->find($request->integer('job'))
            : null;

        return vue('assistant/Show', [
            'conversations' => Conversations::of($user)
                ->with('job.hunt')
                ->latest('updated_at')
                ->limit(50)
                ->get(['id', 'title', 'job_application_id', 'updated_at'])
                ->map(fn (AssistantConversation $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'job' => $getConversationDetails->jobSummary($item->job),
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ]),
            'jobs' => JobApplication::query()
                ->with('hunt')
                ->latest('updated_at')
                ->limit(500)
                ->get()
                ->map($getConversationDetails->jobSummary(...)),
            'newJob' => $getConversationDetails->jobSummary($newJob),
            'portfolioItems' => [
                ...PortfolioSection::mentionables(),
                ...Resume::query()->active()->orderBy('name')->get(['id', 'name'])->map(fn (Resume $resume): array => [
                    'target' => "resume:{$resume->id}",
                    'section' => 'Resumes',
                    'label' => $resume->name,
                ])->all(),
            ],
            'prefill' => $active === null && ($request->filled('mention') || $request->filled('prompt')) ? [
                'target' => $request->filled('mention') ? $request->string('mention')->limit(100, '')->toString() : null,
                'field' => $request->filled('field') ? $request->string('field')->limit(100, '')->toString() : null,
                'prompt' => $request->filled('prompt') ? $request->string('prompt')->limit(500, '')->toString() : null,
            ] : null,
            'conversation' => $active === null ? null : $getConversationDetails->handle($active),
            'isReady' => $settings->hasApiKey(),
            'provider' => $settings->provider->label(),
            'model' => $settings->chatModel(),
        ], new PageMeta(
            title: $active === null ? 'Assistant' : "{$active->title} - Assistant",
        ));
    }

    /**
     * Rename a conversation.
     */
    public function update(AssistantConversationRequest $request, string $conversation): RedirectResponse
    {
        Conversations::of(authUser())->findOrFail($conversation)->update([
            'title' => $request->validated('title'),
        ]);

        toastSuccess('Conversation renamed.');

        return back();
    }

    /**
     * Delete a conversation and its messages.
     */
    public function destroy(string $conversation): RedirectResponse
    {
        $record = Conversations::of(authUser())->findOrFail($conversation);

        $record->messages()->delete();
        $record->delete();

        toastSuccess('Conversation deleted.');

        return to_route('assistant.show');
    }
}
