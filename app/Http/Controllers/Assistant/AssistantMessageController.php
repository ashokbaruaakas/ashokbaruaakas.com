<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Actions\Portfolio\SaveResumeAction;
use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\Conversations;
use App\Ai\JobMentions;
use App\Ai\OpenCodeSession;
use App\Ai\PortfolioEditorContext;
use App\Ai\PortfolioMentions;
use App\Ai\PortfolioSection;
use App\Ai\ProviderError;
use App\Ai\ResumeEditorContext;
use App\Ai\ResumeMentions;
use App\Http\Requests\Assistant\AssistantMessageRequest;
use App\Models\AssistantConversation;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Settings\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @phpstan-import-type ResumeData from SaveResumeAction
 */
final class AssistantMessageController
{
    /** A reply can take several tool calls, each waiting on the provider or on Chrome laying out a resume. */
    private const int REPLY_SECONDS = 300;

    /**
     * Send a message to the assistant and stream its reply as server-sent events.
     *
     * The conversation id comes back in the `X-Conversation-Id` header, so the page can open a conversation the message started.
     * A conversation without a job becomes about the job it was started from, or else the first job its messages mention.
     * A message from the resume editor is about the resume being edited, as its unsaved changes show it, and about the job it is tailored for;
     * a conversation it starts is kept with that resume.
     * A conversation without a resume becomes about the first resume its messages mention, or else the first one the assistant
     * proposes a change to.
     * A message from a portfolio form is about the profile or item being edited, as its unsaved values show it.
     * Proposals and cover letters the assistant creates are tied to its reply once the reply is stored, even when the reply fails.
     */
    public function store(AssistantMessageRequest $request, AiSettings $settings, OpenCodeSession $session, AssistantTurn $turn, SaveResumeAction $saveResume): StreamedResponse|JsonResponse
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

        $editor = $this->resumeEditor($request, $saveResume);
        $mentionedIds = JobMentions::in($message);
        $jobId = $editor?->draft->job_application_id
            ?? $conversation->job_application_id
            ?? ($request->filled('job') ? $request->integer('job') : null)
            ?? $mentionedIds[0]
            ?? null;
        $jobs = JobApplication::query()
            ->with(['events', 'hunt'])
            ->whereKey(array_filter([$jobId, ...$mentionedIds]))
            ->get()
            ->keyBy('id');

        $job = $jobId === null ? null : $jobs->get($jobId);
        $resumeIds = ResumeMentions::in($message);
        $mentionedResumes = array_values(Resume::query()->active()->whereKey($resumeIds)->get()
            ->sortBy(fn (Resume $mentioned): int => (int) array_search($mentioned->id, $resumeIds, true))
            ->all());
        $resume = $conversation?->resume_id === null ? null : Resume::query()->find($conversation->resume_id);

        $stream = resolve(JobHuntAssistant::class)
            ->about($job, $jobs->only($mentionedIds)->values()->all())
            ->aboutResumes($resume, $mentionedResumes)
            ->mentioningPortfolio(PortfolioMentions::in($message))
            ->editingResume($editor)
            ->editingPortfolio($this->portfolioEditor($request))
            ->continueOrStart($conversationId, $user)
            ->stream($message);

        if ($conversation?->resume_id === null) {
            $stream->then(function (AgentResponse $response) use ($editor, $mentionedResumes, $turn): void {
                $resumeId = $editor?->resume->id ?? $mentionedResumes[0]->id ?? $turn->proposedResumeId();

                if ($response->conversationId !== null && $resumeId !== null) {
                    AssistantConversation::query()
                        ->whereKey($response->conversationId)
                        ->whereNull('resume_id')
                        ->update(['resume_id' => $resumeId]);
                }
            });
        }

        $stream->then(fn (AgentResponse $response) => $turn->attachTo($response->conversationId, $response->assistantMessageId));
        $stream->catch(fn () => $turn->attachToLatestReply($stream->conversationId ?? $conversationId));

        if ($job !== null && $conversation?->job_application_id === null) {
            $stream->then(fn (AgentResponse $response) => $response->conversationId === null ? null : AssistantConversation::query()
                ->whereKey($response->conversationId)
                ->whereNull('job_application_id')
                ->update(['job_application_id' => $job->id]));
        }

        return response()->stream(function () use ($stream) {
            set_time_limit(self::REPLY_SECONDS);

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

    private function portfolioEditor(AssistantMessageRequest $request): ?PortfolioEditorContext
    {
        if (! $request->filled('portfolio_draft.section')) {
            return null;
        }

        $section = PortfolioSection::tryFrom($request->string('portfolio_draft.section')->toString());
        $itemId = $request->filled('portfolio_draft.item') ? $request->integer('portfolio_draft.item') : null;

        abort_unless($section instanceof PortfolioSection, 404);

        $item = $section->isList() && $itemId === null ? null : $section->find($itemId);

        abort_if($itemId !== null && ! $item instanceof Model, 404);

        /** @var array<string, mixed> $values */
        $values = $request->validated('portfolio_draft.values', []);

        return PortfolioEditorContext::from($section, $item, $values);
    }

    private function resumeEditor(AssistantMessageRequest $request, SaveResumeAction $saveResume): ?ResumeEditorContext
    {
        if (! $request->filled('resume')) {
            return null;
        }

        $resume = Resume::query()->findOrFail($request->integer('resume'));

        /** @var ResumeData $draft */
        $draft = $request->validated('resume_draft');

        return new ResumeEditorContext(
            resume: $resume,
            draft: $saveResume->draft($resume, $draft),
            previewPages: $request->filled('resume_preview.pages') ? $request->integer('resume_preview.pages') : null,
            previewLastPageFill: $request->filled('resume_preview.last_page_fill') ? $request->float('resume_preview.last_page_fill') : null,
        );
    }
}
