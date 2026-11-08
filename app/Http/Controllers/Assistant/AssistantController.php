<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Ai\Conversations;
use App\DTOs\PageMeta;
use App\Models\AssistantConversation;
use App\Settings\AiSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\ConversationMessage;

final class AssistantController
{
    /**
     * Show the assistant with the owner's recent conversations, and the messages of the open one.
     */
    public function show(AiSettings $settings, ?string $conversation = null): Response
    {
        $user = authUser();
        $active = $conversation !== null ? Conversations::of($user)->findOrFail($conversation) : null;

        return vue('assistant/Show', [
            'conversations' => Conversations::of($user)
                ->latest('updated_at')
                ->limit(50)
                ->get(['id', 'title', 'updated_at'])
                ->map(fn (AssistantConversation $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ]),
            'conversation' => $active === null ? null : [
                'id' => $active->id,
                'title' => $active->title,
                'messages' => $active->messages()
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get(['id', 'role', 'content', 'status'])
                    ->map(fn (ConversationMessage $message): array => [
                        'id' => $message->id,
                        'role' => $message->role,
                        'content' => $message->content,
                        'failed' => $message->status === MessageStatus::Failed,
                    ]),
            ],
            'isReady' => $settings->hasApiKey(),
            'provider' => $settings->provider->label(),
            'model' => $settings->chatModel(),
        ], new PageMeta(
            title: $active === null ? 'Assistant' : "{$active->title} - Assistant",
        ));
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
