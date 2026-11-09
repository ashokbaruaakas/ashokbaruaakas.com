<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\Tools\ProposeJobChange;
use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Tools\Request;

const JOB_CONVERSATION_ID = '01968b4e-8f6a-7b3c-9d2e-bbbbbbbbbbbb';

beforeEach(function (): void {
    $this->travelTo('2026-11-09 10:00:00');
    config(['ai.providers.anthropic.key' => 'sk-ant-test']);
    Http::preventStrayRequests();
    $this->owner = User::factory()->create();
    Conversation::query()->forceCreate([
        'id' => JOB_CONVERSATION_ID,
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Acme',
    ]);
});

function proposeJobChange(array $arguments): string
{
    return new ProposeJobChange(resolve(AssistantTurn::class))->handle(new Request($arguments));
}

function jobProposal(JobApplication $job, array $changes, array $before): AssistantProposal
{
    return AssistantProposal::factory()->create([
        'conversation_id' => JOB_CONVERSATION_ID,
        'section' => 'job',
        'item_id' => $job->id,
        'summary' => 'Mark as applied',
        'changes' => $changes,
        'before' => $before,
    ]);
}

test('a proposed job change is stored with what it replaces, and the job is left alone', function (): void {
    $job = JobApplication::factory()->wishlist()->create(['next_step' => 'Tailor the resume']);

    $result = proposeJobChange([
        'job_id' => $job->id,
        'summary' => 'Mark as applied',
        'changes' => [
            ['field' => 'status', 'value' => 'applied'],
            ['field' => 'applied_on', 'value' => '2026-11-08'],
            ['field' => 'next_step', 'value' => ' '],
        ],
    ]);

    expect($result)->toStartWith('The owner now sees this change as a card')
        ->and(AssistantProposal::query()->sole())
        ->section->toBe('job')
        ->item_id->toBe($job->id)
        ->changes->toBe(['status' => 'applied', 'applied_on' => '2026-11-08', 'next_step' => null])
        ->before->toBe(['status' => 'wishlist', 'applied_on' => null, 'next_step' => 'Tailor the resume'])
        ->and($job->refresh()->status)->toBe(JobApplicationStatus::Wishlist);
});

test('a job change the job form would not accept is turned back to the assistant', function (array $changes, string $error): void {
    $job = JobApplication::factory()->create(['id' => 5, 'status' => JobApplicationStatus::Applied]);

    expect(proposeJobChange(['job_id' => $job->id, 'summary' => 'x', 'changes' => $changes]))->toContain($error)
        ->and(AssistantProposal::query()->count())->toBe(0);
})->with([
    'unknown field' => [[['field' => 'job_hunt_id', 'value' => '2']], 'A job has no field named "job_hunt_id"'],
    'unknown status' => [[['field' => 'status', 'value' => 'ghosted']], 'The change was not proposed: The selected status is invalid.'],
    'bad date' => [[['field' => 'next_step_on', 'value' => 'next Friday']], 'The change was not proposed:'],
    'company removed' => [[['field' => 'company', 'value' => '']], 'The company field is required.'],
    'unchanged' => [[['field' => 'status', 'value' => 'applied']], 'The job already has these values.'],
    'no changes' => [[], 'Propose at least one field change.'],
]);

test('a job change for a job that does not exist is turned back', function (): void {
    expect(proposeJobChange(['job_id' => 99, 'summary' => 'x', 'changes' => [['field' => 'status', 'value' => 'applied']]]))->toBe('There is no job with that id.');
});

test('the assistant can propose job changes during a conversation and shows them as cards', function (): void {
    $job = JobApplication::factory()->wishlist()->create(['company' => 'Acme', 'role' => 'Engineer']);
    JobHuntAssistant::fake(function () use ($job): string {
        proposeJobChange(['job_id' => $job->id, 'summary' => 'Mark as applied', 'changes' => [['field' => 'status', 'value' => 'applied']]]);

        return 'Nice, good luck!';
    });

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), ['message' => 'I just applied', 'conversation' => JOB_CONVERSATION_ID])
        ->streamedContent();

    JobHuntAssistant::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'ProposeJobChange'));

    $this->get(route('assistant.show', JOB_CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.proposals.0.section', 'Job Hunt')
            ->where('conversation.messages.1.proposals.0.item', 'Acme — Engineer')
            ->where('conversation.messages.1.proposals.0.fields.0', ['name' => 'status', 'label' => 'Status', 'before' => 'Wishlist', 'after' => 'Applied']));
});

test('applying a status change puts it on the timeline dated by the applied date, and undo takes it back off', function (): void {
    $job = JobApplication::factory()->wishlist()->create();
    $proposal = jobProposal($job, ['status' => 'applied', 'applied_on' => '2026-11-07', 'next_step' => 'Wait for a reply'], ['status' => 'wishlist', 'applied_on' => null, 'next_step' => null]);
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Change applied.']]);

    $event = $job->events()->sole();

    expect($job->refresh())
        ->status->toBe(JobApplicationStatus::Applied)
        ->next_step->toBe('Wait for a reply')
        ->and($job->applied_on->toDateString())->toBe('2026-11-07')
        ->and($event->type)->toBe(JobApplicationEventType::StatusChange)
        ->and($event->occurred_on->toDateString())->toBe('2026-11-07')
        ->and($proposal->refresh())
        ->status->toBe(ProposalStatus::Applied)
        ->created_item_id->toBe($event->id);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect($job->refresh())
        ->status->toBe(JobApplicationStatus::Wishlist)
        ->applied_on->toBeNull()
        ->next_step->toBeNull()
        ->and($job->events()->count())->toBe(0)
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('a job change is stale once the owner changes the job themselves, and is reported once the job is removed', function (): void {
    $job = JobApplication::factory()->create(['status' => JobApplicationStatus::Applied]);
    $proposal = jobProposal($job, ['status' => 'interviewing'], ['status' => 'applied', 'applied_on' => $job->applied_on->toDateString()]);

    expect($proposal->isStale())->toBeFalse();

    $job->update(['status' => JobApplicationStatus::Rejected]);

    expect($proposal->isStale())->toBeTrue();

    $job->delete();

    $this->actingAs($this->owner)
        ->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'error', 'message' => 'The item this change is for no longer exists.']]);

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});
