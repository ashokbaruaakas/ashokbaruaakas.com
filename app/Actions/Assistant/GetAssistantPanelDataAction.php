<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Ai\Conversations;
use App\Models\AssistantConversation;
use App\Settings\AiSettings;
use Illuminate\Http\Request;
use Inertia\OptionalProp;

final readonly class GetAssistantPanelDataAction
{
    public function __construct(private AiSettings $settings, private GetConversationDetailsAction $getConversationDetails) {}

    /**
     * The props of an assistant panel on an editing page: whether the assistant is set up, the conversations held there,
     * the ones of a resume or else the ones of no resume, and the conversation the panel asks for as the `conversation`
     * query with a partial reload.
     *
     * @return array{assistant: array<string, mixed>, assistantConversation: OptionalProp}
     */
    public function handle(Request $request, ?int $resumeId = null): array
    {
        return [
            'assistant' => [
                'isReady' => $this->settings->hasApiKey(),
                'provider' => $this->settings->provider->label(),
                'model' => $this->settings->chatModel(),
                'conversations' => Conversations::of(authUser())
                    ->where('resume_id', $resumeId)
                    ->latest('updated_at')
                    ->limit(30)
                    ->get(['id', 'title', 'updated_at'])
                    ->map(fn (AssistantConversation $conversation): array => [
                        'id' => $conversation->id,
                        'title' => $conversation->title,
                        'updated_at' => $conversation->updated_at?->toIso8601String(),
                    ])->all(),
            ],
            'assistantConversation' => optionalProp(fn (): ?array => ($conversation = $request->filled('conversation')
                ? Conversations::of(authUser())->find($request->string('conversation')->toString())
                : null) instanceof AssistantConversation ? $this->getConversationDetails->handle($conversation) : null),
        ];
    }
}
