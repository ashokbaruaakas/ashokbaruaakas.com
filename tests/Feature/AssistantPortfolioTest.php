<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\AssistantTurn;
use App\Ai\PortfolioEditorContext;
use App\Ai\PortfolioSection;
use App\Ai\Tools\DraftCoverLetter;
use App\Ai\Tools\ProposePortfolioChange;
use App\Ai\Tools\ReadPortfolio;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\CoverLetter;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\PageContent;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Responses\Data\ToolCall;
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
            ->where('conversation.messages.1.proposals.0.fields.0', ['name' => 'headline', 'label' => 'Headline', 'input' => 'text', 'before' => null, 'after' => 'Senior Laravel Engineer'])
            ->where('conversation.messages.1.coverLetters.0.title', 'Acme')
            ->where('conversation.messages.1.coverLetters.0.download_url', route('assistant.cover-letters.show', CoverLetter::query()->sole()->id)));
});

test('what the assistant creates before its reply fails is still tied to that reply', function (): void {
    JobHuntAssistant::fake([
        new ToolCall('call-1', 'ProposePortfolioChange', ['section' => 'profile', 'summary' => 'Add a headline', 'changes' => [['field' => 'headline', 'value' => 'Senior Laravel Engineer']]]),
        fn () => throw ProviderOverloadedException::forProvider('anthropic'),
    ]);

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), ['message' => 'Improve my profile', 'conversation' => OWNER_CONVERSATION_ID])
        ->streamedContent();

    $reply = Conversation::query()->sole()->messages()->where('role', 'assistant')->sole();

    expect(AssistantProposal::query()->sole()->only(['conversation_id', 'message_id']))->toBe(['conversation_id' => OWNER_CONVERSATION_ID, 'message_id' => $reply->id]);

    $this->get(route('assistant.show', OWNER_CONVERSATION_ID))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.failed', true)
            ->where('conversation.messages.1.proposals.0.summary', 'Add a headline'));
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

test('a change to a field the owner edited since is applied only once they apply it anyway', function (): void {
    $project = Project::factory()->create(['description' => 'Edited by hand']);
    $proposal = ownerProposal(['section' => 'projects', 'item_id' => $project->id, 'changes' => ['description' => 'New'], 'before' => ['description' => 'Old']]);
    $this->actingAs($this->owner);

    $this->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'error', 'message' => 'This changed since the assistant read it. Check the change and apply it anyway to replace your edit.']]);

    expect($project->refresh()->description)->toBe('Edited by hand')
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending);

    $this->post(route('assistant.proposals.apply', $proposal->id), ['force' => true]);

    expect($project->refresh()->description)->toBe('New')
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Applied);
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
        ->assertInertia(fn (Assert $page) => $page->where('prefill', ['target' => 'portfolio:profile', 'field' => 'Headline', 'prompt' => null]));

    $this->actingAs($this->owner)
        ->get(route('assistant.show', ['prompt' => 'Trim my resume']))
        ->assertInertia(fn (Assert $page) => $page->where('prefill', ['target' => null, 'field' => null, 'prompt' => 'Trim my resume']));

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

test('a message from a portfolio form shows the assistant the item as the form shows it', function (): void {
    JobHuntAssistant::fake(['Here is a sharper version.']);
    $experience = Experience::factory()->create(['role' => 'Engineer', 'company' => 'Acme']);

    $this->actingAs($this->owner)
        ->post(route('assistant.messages.store'), [
            'message' => 'Sharpen the role.',
            'portfolio_draft' => ['section' => 'experience', 'item' => $experience->id, 'values' => ['role' => 'Unsaved Staff Engineer', 'secret' => 'not a field']],
        ])
        ->streamedContent();

    JobHuntAssistant::assertPrompted(fn (Laravel\Ai\Prompts\AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Unsaved Staff Engineer')
        && str_contains($prompt->agent->instructions(), "section experience and item_id {$experience->id}")
        && ! str_contains($prompt->agent->instructions(), 'not a field'));
});

test('a message from a portfolio form needs a known section and an item that exists', function (): void {
    $this->actingAs($this->owner)
        ->postJson(route('assistant.messages.store'), ['message' => 'Help.', 'portfolio_draft' => ['section' => 'nonsense', 'values' => []]])
        ->assertJsonValidationErrors(['portfolio_draft.section']);

    $this->actingAs($this->owner)
        ->postJson(route('assistant.messages.store'), ['message' => 'Help.', 'portfolio_draft' => ['section' => 'experience', 'item' => 999, 'values' => []]])
        ->assertNotFound();
});

test('a change to the item a form is editing is compared with its unsaved values, and other items with what is saved', function (): void {
    $editing = Experience::factory()->create(['role' => 'Engineer']);
    $other = Experience::factory()->create(['role' => 'Developer']);
    $editor = PortfolioEditorContext::from(PortfolioSection::tryFrom('experience'), $editing, ['role' => 'Unsaved Engineer']);
    $tool = new ProposePortfolioChange(resolve(AssistantTurn::class), $editor);

    $tool->handle(new Request(['section' => 'experience', 'item_id' => $editing->id, 'summary' => 'Retitle', 'changes' => [['field' => 'role', 'value' => 'Staff Engineer']]]));
    $tool->handle(new Request(['section' => 'experience', 'item_id' => $other->id, 'summary' => 'Retitle', 'changes' => [['field' => 'role', 'value' => 'Lead Developer']]]));

    expect(AssistantProposal::query()->oldest('id')->pluck('before')->all())->toBe([['role' => 'Unsaved Engineer'], ['role' => 'Developer']]);
});

test('the portfolio editors load their assistant conversation on request, with the item each card is for', function (): void {
    $experience = Experience::factory()->create();
    $message = Conversation::query()->findOrFail(OWNER_CONVERSATION_ID)->messages()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-bbbbbbbbbbbb', 'agent' => JobHuntAssistant::class, 'role' => 'assistant', 'content' => 'Try this.',
        'attachments' => [], 'steps' => [], 'usage' => [], 'meta' => [], 'status' => 'completed',
    ]);
    ownerProposal(['section' => 'experience', 'item_id' => $experience->id, 'changes' => ['role' => 'Staff Engineer'], 'before' => ['role' => $experience->role], 'message_id' => $message->id]);
    Conversation::query()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-cccccccccccc',
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Resume chat',
        'resume_id' => Resume::factory()->create()->id,
    ]);

    $this->actingAs($this->owner)
        ->get(route('portfolio.lists.index', ['list' => 'experience', 'conversation' => OWNER_CONVERSATION_ID]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('assistant.isReady', true)
            ->where('assistant.conversations', fn ($conversations): bool => collect($conversations)->pluck('id')->all() === [OWNER_CONVERSATION_ID])
            ->missing('assistantConversation')
            ->reloadOnly('assistantConversation', fn (Assert $reload) => $reload
                ->where('assistantConversation.messages.0.proposals.0.target', "portfolio:experience/{$experience->id}")));

    $this->actingAs($this->owner)
        ->get(route('portfolio.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->has('assistant.conversations', 1));
});

test('the assistant reads the headings and SEO copy, with plain names until it is written', function (): void {
    PageContent::factory()->create(['key' => 'resume', 'title' => 'Ada Lovelace — Resume', 'description' => 'Backend engineer.']);

    $copy = json_decode(new ReadPortfolio()->handle(new Request(['sections' => ['copy']])), true)['copy'];

    expect($copy['page_resume_title'])->toBe('Ada Lovelace — Resume')
        ->and($copy['page_resume_description'])->toBe('Backend engineer.')
        ->and($copy['page_home_title'])->toBe('Home')
        ->and($copy['section_case-studies_title'])->toBe('Case Studies')
        ->and($copy['section_work_flagship'])->toBeNull();
});

test('applying a copy change saves only the records it touches, keeping the rest of each, and undo puts it back', function (): void {
    PageContent::factory()->create(['key' => 'home', 'title' => 'Ada Lovelace', 'description' => 'Old', 'image_alt' => 'Ada smiling']);

    propose(['section' => 'copy', 'summary' => 'Sharpen the home page', 'changes' => [
        ['field' => 'page_home_description', 'value' => 'Senior Laravel engineer building fast, reliable products.'],
        ['field' => 'section_work_flagship', 'value' => 'Flagship Work'],
    ]]);
    $proposal = tap(AssistantProposal::query()->sole())->update(['conversation_id' => OWNER_CONVERSATION_ID]);
    $this->actingAs($this->owner);

    expect($proposal->before)->toBe(['page_home_description' => 'Old', 'section_work_flagship' => null]);

    $this->post(route('assistant.proposals.apply', $proposal->id))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Change applied.']]);

    expect(PageContent::query()->where('key', 'home')->sole()->only(['title', 'description', 'image_alt']))
        ->toBe(['title' => 'Ada Lovelace', 'description' => 'Senior Laravel engineer building fast, reliable products.', 'image_alt' => 'Ada smiling'])
        ->and(App\Models\PortfolioSection::query()->where('key', 'work')->sole()->only(['title', 'labels']))
        ->toBe(['title' => 'Work', 'labels' => ['flagship' => 'Flagship Work', 'caseStudiesLink' => null, 'supporting' => null, 'supportingDescription' => null]])
        ->and(PageContent::query()->count())->toBe(1)
        ->and(App\Models\PortfolioSection::query()->count())->toBe(1);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect(PageContent::query()->where('key', 'home')->sole()->description)->toBe('Old')
        ->and(App\Models\PortfolioSection::query()->where('key', 'work')->sole()->labels['flagship'])->toBeNull();
});

test('a copy change is marked stale once the owner edits the copy, and needs a title', function (): void {
    propose(['section' => 'copy', 'summary' => 'Retitle', 'changes' => [['field' => 'page_resume_title', 'value' => 'Resume — Ada Lovelace']]]);
    $proposal = AssistantProposal::query()->sole();

    expect($proposal->isStale())->toBeFalse();

    PageContent::factory()->create(['key' => 'resume', 'title' => 'Edited by hand']);

    expect($proposal->isStale())->toBeTrue()
        ->and(propose(['section' => 'copy', 'summary' => 'Clear', 'changes' => [['field' => 'page_resume_title', 'value' => ' ']]]))
        ->toStartWith('The change was not proposed');
});

test('the headings and SEO page gives the assistant its copy as the form shows it', function (): void {
    JobHuntAssistant::fake(['Here is a sharper title.']);

    $this->actingAs($this->owner)
        ->get(route('portfolio.copy.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('copy.page_home_title', 'Home')
            ->where('fields.0.name', 'page_home_title')
            ->has('assistant.conversations'));

    $this->post(route('assistant.messages.store'), [
        'message' => 'Sharpen the home title.',
        'portfolio_draft' => ['section' => 'copy', 'item' => null, 'values' => ['page_home_title' => 'Unsaved Home Title']],
    ])->streamedContent();

    JobHuntAssistant::assertPrompted(fn (Laravel\Ai\Prompts\AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Unsaved Home Title')
        && str_contains($prompt->agent->instructions(), 'section copy'));
});

test('plain facts such as the name do not offer the assistant in the profile form', function (): void {
    $this->actingAs($this->owner)
        ->get(route('portfolio.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fields', fn ($fields): bool => collect($fields)->firstWhere('name', 'name')['assisted'] === false
                && collect($fields)->firstWhere('name', 'bio')['assisted'] === true));
});

test('the owner can edit a suggested value before applying it', function (): void {
    $experience = Experience::factory()->create(['highlights' => ['Old highlight'], 'is_current' => false]);
    $proposal = ownerProposal([
        'section' => 'experience',
        'item_id' => $experience->id,
        'changes' => ['highlights' => ['Cut checkout time by 40%'], 'is_current' => true],
        'before' => ['highlights' => ['Old highlight'], 'is_current' => false],
    ]);

    $this->actingAs($this->owner)
        ->post(route('assistant.proposals.apply', $proposal->id), ['changes' => [
            'highlights' => "Cut checkout time by 45%\n\n Led a team of 4 ",
            'is_current' => false,
            'company' => 'Not in the change',
        ]])
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Change applied.']]);

    $experience->refresh();

    expect($experience->highlights)->toBe(['Cut checkout time by 45%', 'Led a team of 4'])
        ->and($experience->is_current)->toBeTrue()
        ->and($experience->company)->not->toBe('Not in the change')
        ->and($proposal->refresh()->changes['highlights'])->toBe(['Cut checkout time by 45%', 'Led a team of 4']);
});

test('saving a form marks the owner suggestions loaded into it as applied', function (): void {
    Profile::factory()->create(['headline' => null]);
    $loaded = ownerProposal();
    $dismissed = ownerProposal(['status' => ProposalStatus::Dismissed]);
    $notLoaded = ownerProposal();
    $othersConversation = Conversation::query()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-bbbbbbbbbbbb',
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => User::factory()->create()->id,
        'title' => 'Someone else',
    ]);
    $others = AssistantProposal::factory()->create(['conversation_id' => $othersConversation->id]);

    $this->actingAs($this->owner)
        ->put(route('portfolio.profile.update'), [
            'name' => 'Owner',
            'headline' => 'Senior Laravel Engineer',
            'assistant_proposal_ids' => [$loaded->id, $dismissed->id, $others->id],
        ])
        ->assertSessionHasNoErrors();

    expect($loaded->refresh()->status)->toBe(ProposalStatus::Applied)
        ->and($dismissed->refresh()->status)->toBe(ProposalStatus::Dismissed)
        ->and($notLoaded->refresh()->status)->toBe(ProposalStatus::Pending)
        ->and($others->refresh()->status)->toBe(ProposalStatus::Pending);
});

test('a new item saved from a suggestion is removed when the suggestion is undone', function (): void {
    $proposal = ownerProposal(['section' => 'languages', 'changes' => ['name' => 'Bangla', 'level' => 'Native'], 'before' => null]);
    $this->actingAs($this->owner);

    $this->post(route('portfolio.lists.store', 'languages'), ['name' => 'Bangla', 'level' => 'Native', 'assistant_proposal_ids' => [$proposal->id]])
        ->assertSessionHasNoErrors();

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Applied);

    $this->post(route('assistant.proposals.undo', $proposal->id));

    expect(App\Models\Language::query()->count())->toBe(0);
});

test('an edited value the portfolio would not accept is turned back without applying the change', function (): void {
    $experience = Experience::factory()->create(['role' => 'Engineer']);
    $proposal = ownerProposal(['section' => 'experience', 'item_id' => $experience->id, 'changes' => ['role' => 'Staff Engineer'], 'before' => ['role' => 'Engineer']]);

    $this->actingAs($this->owner)
        ->post(route('assistant.proposals.apply', $proposal->id), ['changes' => ['role' => '']])
        ->assertSessionHasErrors(['changes.role' => 'The role field is required.']);

    expect($experience->refresh()->role)->toBe('Engineer')
        ->and($proposal->refresh()->status)->toBe(ProposalStatus::Pending)
        ->and($proposal->changes)->toBe(['role' => 'Staff Engineer']);
});
