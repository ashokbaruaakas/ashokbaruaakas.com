<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Ai\Conversations;
use App\DTOs\PageMeta;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Http\Requests\Assistant\AssistantConversationRequest;
use App\Models\AssistantConversation;
use App\Models\AssistantProposal;
use App\Models\CoverLetter;
use App\Models\JobApplication;
use App\Settings\AiSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\ConversationMessage;

final class AssistantController
{
    /**
     * Show the assistant with the owner's recent conversations, and the messages of the open one.
     * A new conversation can start about a job, given as the `job` query.
     */
    public function show(Request $request, AiSettings $settings, ?string $conversation = null): Response
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
                    'job' => $this->jobSummary($item->job),
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ]),
            'jobs' => JobApplication::query()
                ->with('hunt')
                ->latest('updated_at')
                ->limit(500)
                ->get()
                ->map($this->jobSummary(...)),
            'newJob' => $this->jobSummary($newJob),
            'conversation' => $active === null ? null : $this->conversationDetails($active),
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

    /**
     * @return array<string, mixed>
     */
    private function conversationDetails(AssistantConversation $conversation): array
    {
        $proposals = AssistantProposal::query()->where('conversation_id', $conversation->id)->oldest('id')->get();
        $coverLetters = CoverLetter::query()->where('conversation_id', $conversation->id)->oldest('id')->get();

        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'job' => $this->jobSummary($conversation->job),
            'messages' => $conversation->messages()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'role', 'content', 'status', 'created_at'])
                ->map(fn (ConversationMessage $message): array => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'failed' => $message->status === MessageStatus::Failed,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'proposals' => $proposals->where('message_id', $message->id)->map($this->proposalCard(...))->values(),
                    'coverLetters' => $coverLetters->where('message_id', $message->id)->map($this->coverLetterCard(...))->values(),
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalCard(AssistantProposal $proposal): array
    {
        $section = $proposal->portfolioSection();
        $fields = $section?->fields() ?? [];
        $titleField = $section?->list?->titleField();
        $itemLabel = $proposal->addsItem()
            ? ($titleField === null ? null : $proposal->changes[$titleField] ?? null)
            : $section?->itemLabel($section->find($proposal->item_id));

        return [
            'id' => $proposal->id,
            'summary' => $proposal->summary,
            'status' => $proposal->status->value,
            'section' => $section?->title(),
            'item' => is_string($itemLabel) ? $itemLabel : null,
            'adds_item' => $proposal->addsItem(),
            'is_stale' => $proposal->isStale(),
            'fields' => collect($proposal->changes)->map(fn (mixed $value, string $name): array => [
                'name' => $name,
                'label' => ($fields[$name] ?? null) instanceof PortfolioFieldDTO ? $fields[$name]->label : $name,
                'before' => $proposal->before[$name] ?? null,
                'after' => $value,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coverLetterCard(CoverLetter $letter): array
    {
        return [
            'id' => $letter->id,
            'title' => $letter->title,
            'recipient' => $letter->recipient,
            'body' => $letter->body,
            'download_url' => route('assistant.cover-letters.show', $letter->id),
        ];
    }

    /**
     * @return array{id: int, hunt_id: int, hunt: string, company: string, role: string}|null
     */
    private function jobSummary(?JobApplication $job): ?array
    {
        return $job instanceof JobApplication ? [
            'id' => $job->id,
            'hunt_id' => $job->job_hunt_id,
            'hunt' => $job->hunt->title,
            'company' => $job->company,
            'role' => $job->role,
        ] : null;
    }
}
