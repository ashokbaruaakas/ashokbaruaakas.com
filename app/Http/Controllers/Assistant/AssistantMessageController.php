<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\Conversations;
use App\Ai\JobMentions;
use App\Ai\OpenCodeSession;
use App\Ai\PortfolioMentions;
use App\Ai\ProviderError;
use App\Http\Requests\Assistant\AssistantMessageRequest;
use App\Models\AssistantConversation;
use App\Models\JobApplication;
use App\Settings\AiSettings;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AssistantMessageController
{
    /**
     * Send a message to the assistant and stream its reply as server-sent events.
     *
     * The conversation id comes back in the `X-Conversation-Id` header, so the page can open a conversation the message started.
     * A conversation without a job becomes about the job it was started from, or else the first job its messages mention.
     * Proposals and cover letters the assistant creates are tied to its reply once the reply is stored.
     */
    public function store(AssistantMessageRequest $request, AiSettings $settings, OpenCodeSession $session, AssistantTurn $turn): StreamedResponse|JsonResponse
    {
        if (! $settings->hasApiKey()) {
            return response()->json(['message' => "Add an API key for {$settings->provider->label()} under Settings, AI first."], 409);
        }

        $user = authUser();

        /** @var string|null $conversationId */
        $conversationId = $request->validated('conversation');

        $conversation = $conversationId !== null ? Conversations::of($user)->findOrFail($conversationId) : null;

        if ($conversationId !== null) {
            $session->continue($conversationId);
        }

        /** @var string $message */
        $message = $request->validated('message');

        $mentionedIds = JobMentions::in($message);
        $jobId = $conversation->job_application_id
            ?? ($request->filled('job') ? $request->integer('job') : null)
            ?? $mentionedIds[0]
            ?? null;
        $jobs = JobApplication::query()
            ->with(['events', 'hunt'])
            ->whereKey(array_filter([$jobId, ...$mentionedIds]))
            ->get()
            ->keyBy('id');

        $job = $jobId === null ? null : $jobs->get($jobId);

        $stream = resolve(JobHuntAssistant::class)
            ->about($job, $jobs->only($mentionedIds)->values()->all())
            ->mentioningPortfolio(PortfolioMentions::in($message))
            ->continueOrStart($conversationId, $user)
            ->stream($message);

        $stream->then(fn (AgentResponse $response) => $turn->attachTo($response->conversationId, $response->assistantMessageId));

        if ($job !== null && $conversation?->job_application_id === null) {
            $stream->then(fn (AgentResponse $response) => $response->conversationId === null ? null : AssistantConversation::query()
                ->whereKey($response->conversationId)
                ->whereNull('job_application_id')
                ->update(['job_application_id' => $job->id]));
        }

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
