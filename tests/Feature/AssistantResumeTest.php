<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\ResumeChange;
use App\Ai\Tools\ListResumes;
use App\Ai\Tools\ProposeResume;
use App\Enums\ProposalStatus;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\AssistantProposal;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Tools\Request;

const RESUME_CONVERSATION_ID = '01968b4e-8f6a-7b3c-9d2e-dddddddddddd';

beforeEach(function (): void {
    Bus::fake();
    $this->owner = User::factory()->create();
    Conversation::query()->forceCreate([
        'id' => RESUME_CONVERSATION_ID,
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Resumes',
    ]);
    Profile::factory()->create(['tagline' => 'Engineer — Backend', 'professional_summary' => 'Builds things.']);
    $this->lead = Experience::factory()->create(['role' => 'Lead', 'company' => 'Acme', 'sort_order' => 0, 'highlights' => ['Led a team']]);
    $this->junior = Experience::factory()->create(['role' => 'Junior', 'company' => 'Globex', 'sort_order' => 1]);
});

function proposeResume(array $arguments): string
{
    return new ProposeResume(resolve(AssistantTurn::class), resolve(ResumeChange::class))->handle(new Request($arguments));
}

function attachToConversation(): AssistantProposal
{
    $proposal = AssistantProposal::query()->sole();
    $proposal->update(['conversation_id' => RESUME_CONVERSATION_ID]);

    return $proposal;
}

test('a resume for a job is proposed as a card, and nothing is created yet', function (): void {
    $job = JobApplication::factory()->create();

    $result = proposeResume([
        'summary' => 'A one-page resume for Acme',
        'name' => 'Acme',
        'job_id' => $job->id,
        'template' => 'compact',
        'page_limit' => 1,
        'experience' => [$this->lead->id],
        'highlights' => [['experience_id' => $this->lead->id, 'items' => ['Led five engineers on billing']]],
    ]);

    expect($result)->toContain('apply it with one click')
        ->and(AssistantProposal::query()->sole())
        ->section->toBe(ResumeChange::NEW_RESUME_SECTION)
        ->before->toBeNull()
        ->changes->toMatchArray(['name' => 'Acme', 'job_application_id' => $job->id, 'template' => 'compact', 'page_limit' => 1, 'experience' => [$this->lead->id]])
        ->and(Resume::query()->count())->toBe(1);
});

test('applying a proposed resume creates it with its selection and rewording, and undo removes it', function (): void {
    proposeResume([
        'summary' => 'Backend only',
        'name' => 'Backend',
        'experience' => [$this->lead->id],
        'summary_text' => 'Leads backend teams.',
        'highlights' => [['experience_id' => $this->lead->id, 'items' => ['Led five engineers']]],
    ]);
    $proposal = attachToConversation();
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id))->assertRedirect();

    $resume = Resume::query()->where('name', 'Backend')->sole();
    expect($resume->selectionFor(ResumeList::Experience))->toBe([['id' => $this->lead->id, 'included' => true], ['id' => $this->junior->id, 'included' => false]])
        ->and($resume->textOverride('summary'))->toBe('Leads backend teams.')
        ->and($resume->highlightsOverride($this->lead->id))->toBe(['Led five engineers'])
        ->and($resume->is_public)->toBeFalse()
        ->and($proposal->refresh()->created_item_id)->toBe($resume->id);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect(Resume::query()->whereKey($resume->id)->exists())->toBeFalse()
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('a change to a resume keeps only what differs, applies it and undo puts it back', function (): void {
    $resume = Resume::public();

    proposeResume(['summary' => 'Trim to one page', 'resume_id' => $resume->id, 'template' => 'compact', 'page_limit' => 2, 'experience' => [$this->lead->id], 'headline' => 'Staff Engineer — Payments']);
    $proposal = attachToConversation();

    expect($proposal->changes)->toBe(['template' => 'compact', 'experience' => [$this->lead->id], 'headline' => 'Staff Engineer — Payments'])
        ->and($proposal->before)->toBe(['template' => 'classic', 'experience' => [$this->lead->id, $this->junior->id], 'headline' => null]);

    $this->actingAs($this->owner)->post(route('assistant.proposals.apply', $proposal->id));

    $resume->refresh();
    expect($resume->template)->toBe(ResumeTemplate::Compact)
        ->and($resume->textOverride('headline'))->toBe('Staff Engineer — Payments')
        ->and(collect($resume->selectionFor(ResumeList::Experience))->where('included', true)->pluck('id')->all())->toBe([$this->lead->id]);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    $resume->refresh();
    expect($resume->template)->toBe(ResumeTemplate::Classic)
        ->and($resume->textOverride('headline'))->toBeNull()
        ->and(collect($resume->selectionFor(ResumeList::Experience))->where('included', true)->pluck('id')->all())->toBe([$this->lead->id, $this->junior->id]);
});

test('a resume change the editor would not accept is turned back to the assistant', function (array $arguments, string $error): void {
    expect(proposeResume(['summary' => 'x', ...$arguments]))->toContain($error)
        ->and(AssistantProposal::query()->count())->toBe(0);
})->with([
    'no name for a new resume' => [[], 'The name field is required.'],
    'unknown resume' => [['resume_id' => 999], 'There is no resume with that id.'],
    'unknown item' => [['name' => 'X', 'experience' => [999]], 'experience.0'],
    'unknown role for highlights' => [['name' => 'X', 'highlights' => [['experience_id' => 999, 'items' => ['A']]]], 'no experience entry with id 999'],
    'too many pages' => [['name' => 'X', 'page_limit' => 9], 'page limit'],
    'nothing new' => [['resume_id' => 1, 'template' => 'classic'], 'nothing to propose'],
]);

test('a resume change goes stale when the owner edits that part of the resume first', function (): void {
    proposeResume(['summary' => 'Compact', 'resume_id' => Resume::public()->id, 'template' => 'compact']);
    $proposal = attachToConversation();

    expect($proposal->isStale())->toBeFalse();

    Resume::public()->update(['template' => ResumeTemplate::Compact]);

    expect($proposal->fresh()->isStale())->toBeTrue();
});

test('the assistant can list the resumes with what each includes', function (): void {
    $resumes = json_decode(resolve(ListResumes::class)->handle(new Request([])), true);

    expect($resumes[0])->toMatchArray(['name' => 'Main', 'is_public' => true, 'experience' => [$this->lead->id, $this->junior->id], 'headline' => null]);
});

test('the assistant hears about every resume over its page limit', function (): void {
    Resume::factory()->create(['name' => 'Long One', 'page_count' => 3, 'page_limit' => 1]);

    expect(resolve(JobHuntAssistant::class)->instructions())->toContain('"Long One"')->toContain('ProposeResume');
});

test('an applied resume proposal builds its pdf right away, so the page count follows quickly', function (): void {
    $this->freezeTime();
    proposeResume(['summary' => 'Compact', 'resume_id' => Resume::public()->id, 'experience' => [$this->lead->id]]);

    $this->actingAs($this->owner)->post(route('assistant.proposals.apply', attachToConversation()->id));

    Bus::assertDispatched(App\Jobs\RebuildResumePdf::class, fn (App\Jobs\RebuildResumePdf $job): bool => $job->delay->equalTo(now()));
});
