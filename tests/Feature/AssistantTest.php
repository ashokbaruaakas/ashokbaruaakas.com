<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\Agents\JobPostReader;
use App\Ai\Tools\ListJobApplications;
use App\Ai\Tools\ShowJobApplication;
use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Models\AssistantConversation;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobHunt;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request;

const CONVERSATION_ID = '01968b4e-8f6a-7b3c-9d2e-1f4a5b6c7d8e';

beforeEach(function (): void {
    $this->travelTo('2026-11-08 10:00:00');
    config(['ai.providers.anthropic.key' => 'sk-ant-test']);
    Http::preventStrayRequests();
});

function conversationFor(User $user, string $id = CONVERSATION_ID, string $title = 'Follow-ups'): Conversation
{
    return Conversation::query()->forceCreate([
        'id' => $id,
        'participant_type' => $user->getMorphClass(),
        'participant_id' => $user->id,
        'title' => $title,
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function streamedEvents(string $content): array
{
    return collect(explode("\n\n", mb_trim($content)))
        ->map(fn (string $chunk): string => mb_substr($chunk, mb_strlen('data: ')))
        ->reject(fn (string $data): bool => $data === '[DONE]')
        ->map(fn (string $data): array => json_decode($data, true, flags: JSON_THROW_ON_ERROR))
        ->values()
        ->all();
}

test('guests are sent to log in', function (string $method, string $url): void {
    $this->call($method, $url)->assertRedirect(route('login'));
})->with([
    'show' => ['GET', '/assistant'],
    'send' => ['POST', '/assistant/messages'],
    'rename' => ['PUT', '/assistant/' . CONVERSATION_ID],
    'delete' => ['DELETE', '/assistant/' . CONVERSATION_ID],
    'read post' => ['POST', '/job-hunt/read-post'],
]);

test('the assistant lists only the owner conversations and opens one with its messages', function (): void {
    $owner = User::factory()->create();
    $conversation = conversationFor($owner);
    $conversation->messages()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-000000000001', 'agent' => JobHuntAssistant::class, 'role' => 'user', 'content' => 'Which jobs need a follow-up?',
        'attachments' => [], 'steps' => [], 'usage' => [], 'meta' => [], 'status' => 'completed',
    ]);
    conversationFor(User::factory()->create(), '01968b4e-8f6a-7b3c-9d2e-ffffffffffff', 'Someone else');

    $this->actingAs($owner)
        ->get(route('assistant.show', $conversation->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('assistant/Show')
            ->where('isReady', true)
            ->where('conversations.0.title', 'Follow-ups')
            ->count('conversations', 1)
            ->where('conversation.messages', [[
                'id' => '01968b4e-8f6a-7b3c-9d2e-000000000001',
                'role' => 'user',
                'content' => 'Which jobs need a follow-up?',
                'failed' => false,
            ]]));
});

test('another participant conversation cannot be opened, continued or deleted', function (): void {
    conversationFor(User::factory()->create());
    JobHuntAssistant::fake()->preventStrayPrompts();
    $this->actingAs(User::factory()->create());

    $this->get(route('assistant.show', CONVERSATION_ID))->assertNotFound();
    $this->postJson(route('assistant.messages.store'), ['message' => 'Hello', 'conversation' => CONVERSATION_ID])->assertNotFound();
    $this->put(route('assistant.update', CONVERSATION_ID), ['title' => 'Mine now'])->assertNotFound();
    $this->delete(route('assistant.destroy', CONVERSATION_ID))->assertNotFound();

    expect(Conversation::query()->sole()->title)->toBe('Follow-ups');
    JobHuntAssistant::assertNeverPrompted();
});

test('the assistant asks for an API key before it can be used', function (): void {
    config(['ai.providers.anthropic.key' => null]);
    JobHuntAssistant::fake()->preventStrayPrompts();
    $this->actingAs(User::factory()->create());

    $this->get(route('assistant.show'))->assertInertia(fn (Assert $page) => $page->where('isReady', false));
    $this->postJson(route('assistant.messages.store'), ['message' => 'Hello'])
        ->assertConflict()
        ->assertJson(['message' => 'Add an API key for Anthropic under Settings, AI first.']);

    JobHuntAssistant::assertNeverPrompted();
});

test('a message needs text', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson(route('assistant.messages.store'), ['message' => ''])
        ->assertJsonValidationErrors(['message' => 'The message field is required.']);
});

test('a message streams the reply and starts a remembered conversation', function (): void {
    JobHuntAssistant::fake(['Follow up with Acme today.']);
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post(route('assistant.messages.store'), ['message' => 'Which jobs need a follow-up?']);

    $content = $response->streamedContent();
    $conversation = Conversation::query()->sole();
    $deltas = collect(streamedEvents($content))->where('type', 'text_delta')->pluck('delta')->implode('');

    expect($deltas)->toBe('Follow up with Acme today.')
        ->and($response->headers->get('X-Conversation-Id'))->toBe($conversation->id)
        ->and($conversation->participant_id)->toBe($owner->id)
        ->and($conversation->messages()->orderBy('created_at')->orderBy('id')->pluck('content', 'role')->all())
        ->toBe(['user' => 'Which jobs need a follow-up?', 'assistant' => 'Follow up with Acme today.']);

    JobHuntAssistant::assertPrompted('Which jobs need a follow-up?');
});

test('a provider failure mid-stream ends with an error the page can show', function (): void {
    JobHuntAssistant::fake(fn () => throw ProviderOverloadedException::forProvider('anthropic'));

    $content = $this->actingAs(User::factory()->create())
        ->post(route('assistant.messages.store'), ['message' => 'Hello'])
        ->streamedContent();

    expect(collect(streamedEvents($content))->last())->toBe([
        'type' => 'error',
        'message' => 'AI provider [anthropic] is overloaded.',
    ]);
});

test('the owner can delete a conversation with its messages', function (): void {
    $owner = User::factory()->create();
    conversationFor($owner)->messages()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-000000000001', 'agent' => JobHuntAssistant::class, 'role' => 'user', 'content' => 'Hi',
        'attachments' => [], 'steps' => [], 'usage' => [], 'meta' => [], 'status' => 'completed',
    ]);

    $this->actingAs($owner)
        ->delete(route('assistant.destroy', CONVERSATION_ID))
        ->assertRedirect(route('assistant.show'))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Conversation deleted.']]);

    $this->assertDatabaseEmpty('agent_conversations');
    $this->assertDatabaseEmpty('agent_conversation_messages');
});

test('the job list tool filters by status and follow-up', function (): void {
    $hunt = JobHunt::factory()->create();
    JobApplication::factory()->for($hunt, 'hunt')->create(['company' => 'Stale', 'applied_on' => today()->subDays(20)]);
    JobApplication::factory()->for($hunt, 'hunt')->create(['company' => 'Fresh', 'applied_on' => today()->subDays(2)]);
    JobApplication::factory()->for($hunt, 'hunt')->wishlist()->create(['company' => 'Someday']);

    $applied = json_decode(new ListJobApplications()->handle(new Request(['statuses' => ['applied']])), true);
    $stale = json_decode(new ListJobApplications()->handle(new Request(['needs_follow_up' => true])), true);

    expect(array_column($applied['jobs'], 'company'))->toEqualCanonicalizing(['Stale', 'Fresh'])
        ->and(array_column($stale['jobs'], 'company'))->toBe(['Stale'])
        ->and(new ListJobApplications()->handle(new Request(['hunt_id' => 999])))->toBe('There is no job hunt with id 999.');
});

test('the job tool shows a job with its timeline', function (): void {
    $application = JobApplication::factory()->create(['company' => 'Acme', 'role' => 'Engineer']);
    JobApplicationEvent::factory()->for($application, 'application')->create(['type' => JobApplicationEventType::Note, 'body' => 'HR call booked']);
    JobApplicationEvent::factory()->for($application, 'application')->create([
        'type' => JobApplicationEventType::StatusChange, 'body' => null,
        'from_status' => JobApplicationStatus::Wishlist, 'to_status' => JobApplicationStatus::Applied,
    ]);

    $job = json_decode(new ShowJobApplication()->handle(new Request(['id' => $application->id])), true);

    expect($job['company'])->toBe('Acme')
        ->and(array_column($job['timeline'], 'note'))->toContain('HR call booked')
        ->and(array_column($job['timeline'], 'to_status'))->toContain('Applied')
        ->and(new ShowJobApplication()->handle(new Request(['id' => 999])))->toBe('There is no job with that id.');
});

test('a pasted job post fills only the details the form accepts', function (): void {
    JobPostReader::fake([[
        'company' => ' Acme ',
        'role' => 'Senior Engineer',
        'country' => 'Germany',
        'location' => 'Berlin, hybrid',
        'salary' => null,
        'deadline' => '2026-02-30',
        'link' => 'javascript:alert(1)',
        'summary' => 'Builds the billing platform.',
    ]]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => str_repeat('Acme is hiring a senior engineer in Berlin. ', 3)])
        ->assertOk()
        ->assertExactJson([
            'company' => 'Acme',
            'role' => 'Senior Engineer',
            'country' => 'Germany',
            'location' => 'Berlin, hybrid',
            'salary' => null,
            'deadline' => null,
            'link' => null,
            'summary' => 'Builds the billing platform.',
        ]);
});

test('a job post read by a model without structured output is taken from its text', function (): void {
    JobPostReader::fake(["Here you go:\n```json\n{\"company\": \"Acme\", \"role\": \"Engineer\", \"country\": null}\n```"]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => str_repeat('Acme is hiring a senior engineer in Berlin. ', 3)])
        ->assertOk()
        ->assertJson(['company' => 'Acme', 'role' => 'Engineer', 'country' => null]);
});

test('an empty read is asked once more', function (): void {
    JobPostReader::fake(['', ['company' => 'Acme', 'role' => 'Engineer', 'country' => null, 'location' => null, 'salary' => null, 'deadline' => null, 'link' => null, 'summary' => null]]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => str_repeat('Acme is hiring a senior engineer in Berlin. ', 3)])
        ->assertOk()
        ->assertJson(['company' => 'Acme']);

    JobPostReader::assertPromptedTimes(2);
});

test('a job post the model finds nothing in is reported on the post', function (): void {
    JobPostReader::fake(['', '']);

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => str_repeat('Acme is hiring a senior engineer in Berlin. ', 3)])
        ->assertJsonValidationErrors(['text' => 'Anthropic found no job details in the post. Try again, or pick a stronger fast model under Settings, AI.']);
});

test('reading a job post reports a missing key or a provider failure on the post', function (?string $key, Closure $fake, string $message): void {
    config(['ai.providers.anthropic.key' => $key]);
    JobPostReader::fake($fake);

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => str_repeat('Acme is hiring a senior engineer in Berlin. ', 3)])
        ->assertJsonValidationErrors(['text' => $message]);
})->with([
    'no key' => [null, fn () => fn () => [], 'Add an API key for Anthropic under Settings, AI to read job posts.'],
    'provider down' => ['sk-ant-test', fn () => fn () => throw ProviderOverloadedException::forProvider('anthropic'), 'Anthropic could not read the post: AI provider [anthropic] is overloaded.'],
]);

test('a job post must be long enough to read', function (): void {
    JobPostReader::fake()->preventStrayPrompts();

    $this->actingAs(User::factory()->create())
        ->postJson(route('job-hunt.read-post'), ['text' => 'Too short'])
        ->assertJsonValidationErrors(['text' => 'The text field must be at least 50 characters.']);

    JobPostReader::assertNeverPrompted();
});

test('the assistant knows the owner resume, or that there is none yet', function (): void {
    expect(resolve(JobHuntAssistant::class)->instructions())->toContain('The owner has not filled in their portfolio yet');

    Profile::factory()->create(['name' => 'Ada Lovelace']);

    expect(resolve(JobHuntAssistant::class)->instructions())->toContain('Ada Lovelace')
        ->toContain('Today is 2026-11-08.');
});

test('a job opens its latest conversation, or starts one about it', function (): void {
    $owner = User::factory()->create();
    $application = JobApplication::factory()->create(['company' => 'Acme', 'role' => 'Engineer']);
    $chatUrl = route('job-hunt.applications.chat', ['jobHunt' => $application->job_hunt_id, 'application' => $application->id]);

    $this->actingAs($owner)->get($chatUrl)->assertRedirect(route('assistant.show', ['job' => $application->id]));

    $this->get(route('assistant.show', ['job' => $application->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('newJob', ['id' => $application->id, 'hunt_id' => $application->job_hunt_id, 'hunt' => $application->hunt->title, 'company' => 'Acme', 'role' => 'Engineer'])
            ->where('jobs.0.company', 'Acme'));

    conversationFor($owner)->forceFill(['job_application_id' => $application->id])->save();

    $this->get($chatUrl)->assertRedirect(route('assistant.show', ['conversation' => CONVERSATION_ID]));
    $this->get(route('assistant.show', CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.job.company', 'Acme')
            ->where('conversations.0.job.company', 'Acme')
            ->where('newJob', null));
});

test('a conversation started from a job is about it', function (): void {
    JobHuntAssistant::fake(['Follow up on Friday.']);
    $application = JobApplication::factory()->create(['company' => 'Acme', 'role' => 'Engineer', 'notes' => 'Met the CTO at a meetup']);

    $this->actingAs(User::factory()->create())
        ->post(route('assistant.messages.store'), ['message' => 'What next?', 'job' => $application->id])
        ->streamedContent();

    expect(AssistantConversation::query()->sole()->job_application_id)->toBe($application->id);
    JobHuntAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'This conversation is about one job')
        && str_contains($prompt->agent->instructions(), 'Met the CTO at a meetup'));
});

test('a mentioned job is shown to the assistant and a conversation without a job becomes about the first one', function (): void {
    JobHuntAssistant::fake(['Both look good.', 'Sure.']);
    $owner = User::factory()->create();
    $acme = JobApplication::factory()->create(['company' => 'Acme', 'role' => 'Engineer', 'notes' => 'Acme notes']);
    $globex = JobApplication::factory()->create(['company' => 'Globex', 'role' => 'Lead', 'notes' => 'Globex notes']);
    $message = "Compare @[Acme — Engineer](job:{$acme->id}) with @[Globex — Lead](job:{$globex->id})";

    $this->actingAs($owner)->post(route('assistant.messages.store'), ['message' => $message])->streamedContent();

    $conversation = AssistantConversation::query()->sole();

    expect($conversation->job_application_id)->toBe($acme->id);
    JobHuntAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Acme notes')
        && str_contains($prompt->agent->instructions(), 'The jobs the owner mentions in their latest message')
        && str_contains($prompt->agent->instructions(), 'Globex notes'));

    $this->post(route('assistant.messages.store'), [
        'message' => "And @[Globex — Lead](job:{$globex->id})?",
        'conversation' => $conversation->id,
    ])->streamedContent();

    expect($conversation->refresh()->job_application_id)->toBe($acme->id);
});

test('a conversation stays when its job is removed', function (): void {
    $application = JobApplication::factory()->create();
    conversationFor(User::factory()->create())->forceFill(['job_application_id' => $application->id])->save();

    $application->delete();

    expect(AssistantConversation::query()->sole()->job_application_id)->toBeNull();
});

test('a message can only start about a job that exists', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson(route('assistant.messages.store'), ['message' => 'Hello', 'job' => 999])
        ->assertJsonValidationErrors(['job' => 'The selected job is invalid.']);
});

test('the owner can rename a conversation', function (): void {
    $owner = User::factory()->create();
    conversationFor($owner);

    $this->actingAs($owner)
        ->from(route('assistant.show', CONVERSATION_ID))
        ->put(route('assistant.update', CONVERSATION_ID), ['title' => '  Acme interview prep  '])
        ->assertRedirect(route('assistant.show', CONVERSATION_ID))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Conversation renamed.']]);

    expect(Conversation::query()->sole()->title)->toBe('Acme interview prep');
});

test('a conversation title is required and kept short', function (string $title, string $error): void {
    $owner = User::factory()->create();
    conversationFor($owner);

    $this->actingAs($owner)
        ->put(route('assistant.update', CONVERSATION_ID), ['title' => $title])
        ->assertSessionHasErrors(['title' => $error]);

    expect(Conversation::query()->sole()->title)->toBe('Follow-ups');
})->with([
    'empty' => ['   ', 'The title field is required.'],
    'too long' => [str_repeat('a', 121), 'The title field must not be greater than 120 characters.'],
]);
