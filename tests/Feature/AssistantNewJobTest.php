<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\Tools\ProposeNewJob;
use App\Enums\JobApplicationStatus;
use App\Enums\ProposalStatus;
use App\Enums\WorkMode;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Tools\Request;

const NEW_JOB_CONVERSATION_ID = '01968b4e-8f6a-7b3c-9d2e-cccccccccccc';

beforeEach(function (): void {
    $this->travelTo('2026-11-10 10:00:00');
    config(['ai.providers.anthropic.key' => 'sk-ant-test']);
    Http::preventStrayRequests();
    $this->owner = User::factory()->create();
    Conversation::query()->forceCreate([
        'id' => NEW_JOB_CONVERSATION_ID,
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Acme post',
    ]);
});

function proposeNewJob(array $arguments): string
{
    return new ProposeNewJob(resolve(AssistantTurn::class))->handle(new Request($arguments));
}

function newJobFields(array $extra = []): array
{
    return collect(['company' => 'Acme', 'role' => 'Backend Engineer', 'link' => 'https://acme.test/jobs/42', ...$extra])
        ->map(fn (string $value, string $field): array => ['field' => $field, 'value' => $value])
        ->values()
        ->all();
}

test('a new job is proposed for the running hunt as a wishlist job, and nothing is added yet', function (): void {
    JobHunt::factory()->ended()->create();
    $running = JobHunt::factory()->create();

    $result = proposeNewJob(['summary' => 'Track Acme', 'fields' => newJobFields(['work_mode' => 'remote', 'salary' => ' '])]);

    expect($result)->toContain('can add it to the hunt')
        ->and(AssistantProposal::query()->sole())
        ->section->toBe('new_job')
        ->item_id->toBe($running->id)
        ->before->toBeNull()
        ->changes->toBe(['status' => 'wishlist', 'company' => 'Acme', 'role' => 'Backend Engineer', 'link' => 'https://acme.test/jobs/42', 'work_mode' => 'remote'])
        ->and(JobApplication::query()->count())->toBe(0);
});

test('a new job the job form would not accept, or one the hunt already has, is turned back to the assistant', function (Closure $arguments, string $error): void {
    $hunt = JobHunt::factory()->create();
    JobApplication::factory()->for($hunt, 'hunt')->create(['company' => 'Globex', 'role' => 'Data Engineer', 'link' => 'https://globex.test/jobs/7']);

    expect(proposeNewJob($arguments($hunt)))->toContain($error)
        ->and(AssistantProposal::query()->count())->toBe(0);
})->with([
    'no role' => [fn (JobHunt $hunt): array => ['summary' => 'x', 'fields' => [['field' => 'company', 'value' => 'Acme']]], 'The role field is required.'],
    'unknown field' => [fn (JobHunt $hunt): array => ['summary' => 'x', 'fields' => newJobFields(['job_hunt_id' => '2'])], 'A job has no field named "job_hunt_id"'],
    'bad work mode' => [fn (JobHunt $hunt): array => ['summary' => 'x', 'fields' => newJobFields(['work_mode' => 'anywhere'])], 'The job was not proposed:'],
    'same link' => [fn (JobHunt $hunt): array => ['summary' => 'x', 'fields' => newJobFields(['link' => 'https://globex.test/jobs/7'])], 'This hunt already has this job'],
    'same company and role' => [fn (JobHunt $hunt): array => ['summary' => 'x', 'fields' => newJobFields(['company' => 'globex', 'role' => 'data engineer'])], 'This hunt already has this job'],
    'unknown hunt' => [fn (JobHunt $hunt): array => ['hunt_id' => 99, 'summary' => 'x', 'fields' => newJobFields()], 'There is no hunt with that id.'],
]);

test('a new job needs a running hunt unless one is named', function (): void {
    $ended = JobHunt::factory()->ended()->create();

    expect(proposeNewJob(['summary' => 'x', 'fields' => newJobFields()]))->toContain('The owner has no running job hunt.')
        ->and(proposeNewJob(['hunt_id' => $ended->id, 'summary' => 'x', 'fields' => newJobFields()]))->toContain('can add it to the hunt');
});

test('adding a proposed job puts it in the hunt with its timeline, and undo removes it', function (): void {
    $hunt = JobHunt::factory()->create(['title' => 'Autumn 2026']);
    $proposal = AssistantProposal::factory()->create([
        'conversation_id' => NEW_JOB_CONVERSATION_ID,
        'section' => 'new_job',
        'item_id' => $hunt->id,
        'summary' => 'Track Acme',
        'changes' => ['status' => 'applied', 'company' => 'Acme', 'role' => 'Backend Engineer', 'work_mode' => 'remote'],
        'before' => null,
    ]);
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Job added.']]);

    $job = $hunt->applications()->sole();

    expect($job)
        ->company->toBe('Acme')
        ->status->toBe(JobApplicationStatus::Applied)
        ->work_mode->toBe(WorkMode::Remote)
        ->and($job->applied_on->toDateString())->toBe('2026-11-10')
        ->and($job->events()->count())->toBe(1)
        ->and($proposal->refresh())
        ->status->toBe(ProposalStatus::Applied)
        ->created_item_id->toBe($job->id);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect(JobApplication::query()->count())->toBe(0)
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('the assistant can propose a job from a shared link as a card that links to the job once added', function (): void {
    $hunt = JobHunt::factory()->create(['title' => 'Autumn 2026']);
    JobHuntAssistant::fake(function (): string {
        proposeNewJob(['summary' => 'Track Acme', 'fields' => newJobFields()]);

        return 'Added it as a card.';
    });

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), ['message' => 'Track https://acme.test/jobs/42', 'conversation' => NEW_JOB_CONVERSATION_ID])
        ->streamedContent();

    JobHuntAssistant::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'ReadWebPage')
        && str_contains($prompt->agent->instructions(), "\"id\":{$hunt->id}"));

    $this->get(route('assistant.show', NEW_JOB_CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.proposals.0.section', 'Job Hunt')
            ->where('conversation.messages.1.proposals.0.item', 'Autumn 2026')
            ->where('conversation.messages.1.proposals.0.adds_item', true)
            ->where('conversation.messages.1.proposals.0.link', null)
            ->where('conversation.messages.1.proposals.0.fields.1', ['name' => 'company', 'label' => 'Company', 'before' => null, 'after' => 'Acme']));

    $this->post(route('assistant.proposals.apply', AssistantProposal::query()->sole()->id));
    $job = $hunt->applications()->sole();

    $this->get(route('assistant.show', NEW_JOB_CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.proposals.0.link', route('job-hunt.show', [$hunt->id, 'job' => $job->id])));
});
