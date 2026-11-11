<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\Tools\DraftCoverLetter;
use App\Ai\Tools\ProposePortfolioChange;
use App\Ai\Tools\ReadPortfolio;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\CoverLetter;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Tools\Request;

const OWNER_CONVERSATION_ID = '01968b4e-8f6a-7b3c-9d2e-aaaaaaaaaaaa';

beforeEach(function (): void {
    $this->travelTo('2026-11-09 10:00:00');
    config(['ai.providers.anthropic.key' => 'sk-ant-test', 'services.chrome.path' => PHP_BINARY]);
    Http::preventStrayRequests();
    Bus::fake();
    $this->owner = User::factory()->create();
    Conversation::query()->forceCreate([
        'id' => OWNER_CONVERSATION_ID,
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Portfolio',
    ]);
});

function propose(array $arguments): string
{
    return new ProposePortfolioChange(resolve(AssistantTurn::class))->handle(new Request($arguments));
}

function ownerProposal(array $attributes = []): AssistantProposal
{
    return AssistantProposal::factory()->create(['conversation_id' => OWNER_CONVERSATION_ID, ...$attributes]);
}

test('the portfolio tool reads every item with its id, hidden and flagship projects included', function (): void {
    Profile::factory()->create(['name' => 'Ada Lovelace', 'how_i_work' => 'Small, reviewed steps.']);
    $flagship = Project::factory()->create(['name' => 'Engine', 'tier' => 1]);
    Project::factory()->create(['name' => 'Draft', 'is_visible' => false, 'sort_order' => 1]);

    $portfolio = json_decode(new ReadPortfolio()->handle(new Request(['sections' => ['profile', 'projects', 'nonsense']])), true);

    expect($portfolio['profile']['how_i_work'])->toBe('Small, reviewed steps.')
        ->and(array_column($portfolio['projects'], 'name'))->toBe(['Engine', 'Draft'])
        ->and($portfolio['projects'][0]['id'])->toBe($flagship->id)
        ->and($portfolio)->not->toHaveKey('nonsense')
        ->and(new ReadPortfolio()->handle(new Request(['sections' => []])))->toStartWith('Name at least one section to read');
});

test('a proposed change is stored for the owner to apply, with what it replaces', function (): void {
    $experience = Experience::factory()->create(['role' => 'Engineer', 'highlights' => ['Old highlight']]);

    $result = propose([
        'section' => 'experience',
        'item_id' => $experience->id,
        'summary' => 'Add measurable highlights',
        'changes' => [['field' => 'highlights', 'value' => "Cut checkout time by 40%\n\n  Led a team of 4  "]],
    ]);

    expect($result)->toStartWith('The owner now sees this change as a card')
        ->and(AssistantProposal::query()->sole())
        ->section->toBe('experience')
        ->item_id->toBe($experience->id)
        ->changes->toBe(['highlights' => ['Cut checkout time by 40%', 'Led a team of 4']])
        ->before->toBe(['highlights' => ['Old highlight']])
        ->status->toBe(ProposalStatus::Pending)
        ->and($experience->refresh()->highlights)->toBe(['Old highlight']);
});

test('a change the portfolio would not accept is turned back to the assistant', function (array $arguments, string $error): void {
    Project::factory()->create(['id' => 7, 'tier' => 1]);

    expect(propose($arguments))->toContain($error)
        ->and(AssistantProposal::query()->count())->toBe(0);
})->with([
    'unknown section' => [['section' => 'secrets', 'summary' => 'x', 'changes' => [['field' => 'name', 'value' => 'x']]], 'Unknown section'],
    'missing item' => [['section' => 'projects', 'item_id' => 99, 'summary' => 'x', 'changes' => [['field' => 'name', 'value' => 'x']]], 'There is no item with id 99 in Projects.'],
    'unknown field' => [['section' => 'projects', 'item_id' => 7, 'summary' => 'x', 'changes' => [['field' => 'owner_password', 'value' => 'x']]], 'Projects has no field named "owner_password"'],
    'links are not proposed' => [['section' => 'projects', 'item_id' => 7, 'summary' => 'x', 'changes' => [['field' => 'links', 'value' => 'x']]], 'Projects has no field named "links"'],
    'invalid value' => [['section' => 'projects', 'item_id' => 7, 'summary' => 'x', 'changes' => [['field' => 'demo_url', 'value' => 'javascript:alert(1)']]], 'The change was not proposed: The demo url field must be a valid URL.'],
    'new item without required fields' => [['section' => 'languages', 'summary' => 'x', 'changes' => [['field' => 'name', 'value' => 'Bangla']]], 'The level field is required.'],
    'unchanged value' => [['section' => 'projects', 'item_id' => 7, 'summary' => 'x', 'changes' => [['field' => 'tier', 'value' => '1']]], 'Those fields already have these values.'],
    'no changes' => [['section' => 'profile', 'summary' => 'x', 'changes' => []], 'Propose at least one field change.'],
]);

test('what the assistant creates in a turn is tied to its reply', function (): void {
    JobHuntAssistant::fake(function (): string {
        propose(['section' => 'profile', 'summary' => 'Add a headline', 'changes' => [['field' => 'headline', 'value' => 'Senior Laravel Engineer']]]);
        new DraftCoverLetter(resolve(AssistantTurn::class))->handle(new Request(['title' => 'Acme', 'body' => str_repeat('I build things. ', 10)]));

        return 'Here you go.';
    });

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), ['message' => 'Improve my profile', 'conversation' => OWNER_CONVERSATION_ID])
        ->streamedContent();

    $reply = Conversation::query()->sole()->messages()->where('role', 'assistant')->sole();

    expect(AssistantProposal::query()->sole()->only(['conversation_id', 'message_id']))->toBe(['conversation_id' => OWNER_CONVERSATION_ID, 'message_id' => $reply->id])
        ->and(CoverLetter::query()->sole()->message_id)->toBe($reply->id);

    $this->get(route('assistant.show', OWNER_CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.proposals.0.summary', 'Add a headline')
            ->where('conversation.messages.1.proposals.0.fields.0', ['name' => 'headline', 'label' => 'Headline', 'before' => null, 'after' => 'Senior Laravel Engineer'])
            ->where('conversation.messages.1.coverLetters.0.title', 'Acme')
            ->where('conversation.messages.1.coverLetters.0.download_url', route('assistant.cover-letters.show', CoverLetter::query()->sole()->id)));
});

test('applying a change saves it, and undo puts back what it replaced', function (): void {
    $project = Project::factory()->create(['name' => 'Engine', 'description' => 'Old']);
    $project->links()->create(['label' => 'Docs', 'url' => 'https://example.com', 'sort_order' => 0]);
    $proposal = ownerProposal(['section' => 'projects', 'item_id' => $project->id, 'changes' => ['description' => 'New'], 'before' => ['description' => 'Old']]);
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Change applied.']]);

    expect($project->refresh()->description)->toBe('New')
        ->and($project->links()->count())->toBe(1)
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Applied);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect($project->refresh()->description)->toBe('Old')
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('applying a new item adds it, and undo removes it', function (): void {
    $proposal = ownerProposal(['section' => 'languages', 'changes' => ['name' => 'Bangla', 'level' => 'Native'], 'before' => null]);
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id));

    expect(App\Models\Language::query()->sole()->only(['name', 'level']))->toBe(['name' => 'Bangla', 'level' => 'Native']);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect(App\Models\Language::query()->count())->toBe(0);
});

test('a profile change updates the profile', function (): void {
    Profile::factory()->create(['headline' => null]);

    $this->actingAs($this->owner)->post(route('assistant.proposals.apply', ownerProposal()->id));

    expect(Profile::query()->sole()->headline)->toBe('Senior Laravel Engineer');
});

test('a dismissed change is not applied', function (): void {
    $proposal = ownerProposal();
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.dismiss', $proposal->id))->assertRedirect();
    $this->post(route('assistant.proposals.apply', $proposal->id))->assertNotFound();

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Dismissed)
        ->and(Profile::query()->count())->toBe(0);
});

test('a change to an item that was removed is reported instead of applied', function (): void {
    $proposal = ownerProposal(['section' => 'projects', 'item_id' => 99, 'changes' => ['description' => 'New'], 'before' => ['description' => 'Old']]);

    $this->actingAs($this->owner)
        ->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'error', 'message' => 'The item this change is for no longer exists.']]);

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('a change is marked stale once the owner edits the field themselves', function (): void {
    $project = Project::factory()->create(['tier' => 1, 'description' => 'Old']);
    $proposal = ownerProposal(['section' => 'projects', 'item_id' => $project->id, 'changes' => ['description' => 'New'], 'before' => ['tier' => '1', 'description' => 'Old']]);

    expect($proposal->isStale())->toBeFalse();

    $project->update(['description' => 'Edited by hand']);

    expect($proposal->isStale())->toBeTrue();
});

test('another participant cannot apply, undo or dismiss a change, or download a letter', function (): void {
    $proposal = ownerProposal();
    $applied = ownerProposal(['status' => ProposalStatus::Applied]);
    $letter = CoverLetter::factory()->create(['conversation_id' => OWNER_CONVERSATION_ID]);
    Process::fake();
    $this->actingAs(User::factory()->create());

    $this->post(route('assistant.proposals.apply', $proposal->id))->assertNotFound();
    $this->post(route('assistant.proposals.dismiss', $proposal->id))->assertNotFound();
    $this->post(route('assistant.proposals.undo', $applied->id))->assertNotFound();
    $this->get(route('assistant.cover-letters.show', $letter->id))->assertNotFound();

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Pending);
    Process::assertNothingRan();
});

test('a cover letter needs a whole body and a job that exists', function (): void {
    $tool = new DraftCoverLetter(resolve(AssistantTurn::class));

    expect($tool->handle(new Request(['title' => 'Acme', 'body' => 'Too short.'])))->toBe('Write the whole letter in the body.')
        ->and($tool->handle(new Request(['title' => 'Acme', 'job_id' => 99, 'body' => str_repeat('a', 200)])))->toBe('There is no job with id 99.')
        ->and(CoverLetter::query()->count())->toBe(0);
});

test('a cover letter downloads as a PDF under the owner name and contact details', function (): void {
    Profile::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'location' => 'London', 'timezone_label' => 'UTC+0', 'phone' => null, 'phone_display' => null]);
    $letter = CoverLetter::factory()->create([
        'conversation_id' => OWNER_CONVERSATION_ID,
        'job_application_id' => JobApplication::factory()->create(['company' => 'Acme Corp'])->id,
        'body' => "Dear team,\n\nI <b>build</b> things.\n\nBest,\nAda",
    ]);
    $printed = '';
    Process::fake(function (PendingProcess $process) use (&$printed): ProcessResult {
        $printed = file_get_contents(mb_substr((string) end($process->command), mb_strlen('file://')));
        $output = collect($process->command)->first(fn (string $argument): bool => str_starts_with($argument, '--print-to-pdf='));
        file_put_contents(mb_substr($output, mb_strlen('--print-to-pdf=')), str_repeat('%', 2048));

        return Process::result();
    });

    $response = $this->actingAs($this->owner)->get(route('assistant.cover-letters.show', $letter->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="Ada-Lovelace-Cover-Letter-Acme-Corp.pdf"');
    expect($response->getContent())->toBe(str_repeat('%', 2048))
        ->and($printed)->toContain('Ada Lovelace')
        ->toContain('Email: ada@example.com')
        ->toContain('Location: London (UTC+0)')
        ->not->toContain('Phone:')
        ->toContain('November 9, 2026')
        ->toContain('I &lt;b&gt;build&lt;/b&gt; things.');
});

test('the page lists the profile and every portfolio item for mentions', function (): void {
    $project = Project::factory()->create(['name' => 'Engine']);

    $this->actingAs($this->owner)
        ->get(route('assistant.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('portfolioItems.0', ['target' => 'portfolio:profile', 'section' => 'Profile', 'label' => 'Profile'])
            ->where('portfolioItems.1', ['target' => "portfolio:projects/{$project->id}", 'section' => 'Projects', 'label' => 'Engine']));
});

test('a new conversation can open with a portfolio item mentioned, but an open one cannot', function (): void {
    $this->actingAs($this->owner)
        ->get(route('assistant.show', ['mention' => 'portfolio:profile', 'field' => 'Headline']))
        ->assertInertia(fn (Assert $page) => $page->where('prefill', ['target' => 'portfolio:profile', 'field' => 'Headline']));

    $this->actingAs($this->owner)
        ->get(route('assistant.show', ['conversation' => OWNER_CONVERSATION_ID, 'mention' => 'portfolio:profile']))
        ->assertInertia(fn (Assert $page) => $page->where('prefill', null));
});

test('a mentioned portfolio item is shown to the assistant as saved, and a missing one is left out', function (): void {
    JobHuntAssistant::fake(['Here is a sharper version.']);
    Profile::factory()->create(['headline' => 'Laravel developer']);
    $project = Project::factory()->create(['name' => 'Engine', 'description' => 'A queue-backed checkout engine']);
    $message = "Rewrite @[Projects · Engine](portfolio:projects/{$project->id}), @[Profile](portfolio:profile) and @[Projects · Gone](portfolio:projects/999)";

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), ['message' => $message, 'conversation' => OWNER_CONVERSATION_ID])
        ->streamedContent();

    JobHuntAssistant::assertPrompted(fn (Laravel\Ai\Prompts\AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'The parts of the portfolio the owner mentions')
        && str_contains($prompt->agent->instructions(), 'A queue-backed checkout engine')
        && str_contains($prompt->agent->instructions(), '"section":"profile"')
        && str_contains($prompt->agent->instructions(), 'Laravel developer')
        && ! str_contains($prompt->agent->instructions(), '"id":999'));
});
