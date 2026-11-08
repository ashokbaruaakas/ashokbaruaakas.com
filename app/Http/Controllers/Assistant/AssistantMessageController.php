<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\Conversations;
use App\Ai\ProviderError;
use App\Http\Requests\Assistant\AssistantMessageRequest;
use App\Settings\AiSettings;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AssistantMessageController
{
    /**
     * Send a message to the assistant and stream its reply as server-sent events.
     *
     * The conversation id comes back in the `X-Conversation-Id` header, so the page can open a conversation the message started.
     */
    public function store(AssistantMessageRequest $request, AiSettings $settings): StreamedResponse|JsonResponse
    {
        if (! $settings->hasApiKey()) {
            return response()->json(['message' => "Add an API key for {$settings->provider->label()} under Settings, AI first."], 409);
        }

        $user = authUser();

        /** @var string|null $conversationId */
        $conversationId = $request->validated('conversation');

        if ($conversationId !== null) {
            Conversations::of($user)->findOrFail($conversationId);
        }

        /** @var string $message */
        $message = $request->validated('message');

        $stream = resolve(JobHuntAssistant::class)
            ->continueOrStart($conversationId, $user)
            ->stream($message);

        return response()->stream(function () use ($stream) {
            try {
                foreach ($stream as $event) {
                    if ($event instanceof StreamEvent) {
                        yield 'data: ' . $event . "\n\n";
                    }
                }
            } catch (Throwable $exception) {
                report($exception);

                yield 'data: ' . json_encode([
                    'type' => 'error',
                    'message' => ProviderError::describe($exception) ?? 'The assistant ran into a problem. Please try again.',
                ], JSON_THROW_ON_ERROR) . "\n\n";
            }

            yield "data: [DONE]\n\n";
        }, headers: [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'X-Conversation-Id' => $stream->conversationId ?? $conversationId,
        ]);
    }
}
