<?php

declare(strict_types=1);

use App\Actions\Portfolio\MeasureResumeAction;
use App\Actions\Portfolio\SaveResumeAction;
use App\Ai\Agents\JobHuntAssistant;
use App\Ai\Agents\ResumeRewriter;
use App\Ai\AssistantTurn;
use App\Ai\ResumeChange;
use App\Ai\ResumeEditorContext;
use App\Ai\Tools\MeasureResume;
use App\Ai\Tools\ProposeResume;
use App\Enums\ResumeTemplate;
use App\Models\AssistantProposal;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request;

beforeEach(function (): void {
    Bus::fake();
    Http::preventStrayRequests();
    config(['ai.providers.anthropic.key' => 'sk-ant-test', 'services.chrome.path' => PHP_BINARY]);
    $this->owner = User::factory()->create();
    Profile::factory()->create(['name' => 'Ada Lovelace', 'tagline' => 'Engineer — Backend', 'professional_summary' => 'Builds things.']);
    $this->lead = Experience::factory()->create(['role' => 'Lead', 'company' => 'Acme', 'sort_order' => 0, 'highlights' => ['Led a team']]);
    $this->junior = Experience::factory()->create(['role' => 'Junior', 'company' => 'Globex', 'sort_order' => 1]);
    $this->resume = Resume::factory()->create(['name' => 'Backend', 'template' => ResumeTemplate::Classic]);
});

/**
 * @param  array<string, mixed>  $changes
 */
function editorContext(Resume $resume, array $changes = [], ?int $pages = null, ?float $fill = null): ResumeEditorContext
{
    $data = ['name' => $resume->name, 'template' => $resume->template->value, ...$changes];

    return new ResumeEditorContext($resume, resolve(SaveResumeAction::class)->draft($resume, $data), $pages, $fill);
}

/**
 * Fake Chrome laying the measure page out, after checking what the page shows.
 */
function fakeMeasuringBrowser(int $pages, float $fill, ?Closure $inspect = null): void
{
    Process::fake(function (PendingProcess $process) use ($pages, $fill, $inspect): ProcessResult {
        expect($process->command)->toContain('--dump-dom');

        if ($inspect instanceof Closure) {
            $inspect(mb_substr((string) end($process->command), mb_strlen(url('/'))));
        }

        return Process::result("<html data-resume-pages=\"{$pages}\" data-resume-last-page-fill=\"{$fill}\"><body></body></html>");
    });
}

test('the assistant measures the resume being edited with the changes it considers, without saving them', function (): void {
    $editor = editorContext($this->resume, ['overrides' => ['headline' => 'Unsaved headline']]);

    fakeMeasuringBrowser(1, 0.82, function (string $path): void {
        $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('resume/Measure')
            ->where('design.template', 'compact')
            ->where('portfolio.tagline', 'Unsaved headline')
            ->count('portfolio.experience', 1));
    });

    $result = new MeasureResume(resolve(MeasureResumeAction::class), resolve(ResumeChange::class), $editor)
        ->handle(new Request(['resume_id' => $this->resume->id, 'template' => 'compact', 'experience' => [$this->lead->id]]));

    expect(json_decode($result, true))->toBe(['pages' => 1, 'last_page_full_percent' => 82])
        ->and($this->resume->fresh())->template->toBe(ResumeTemplate::Classic)
        ->and(AssistantProposal::query()->count())->toBe(0);
});

test('a measure page is reached only through its signed link, while the draft lasts', function (): void {
    $this->get("/resumes/{$this->resume->slug}/measure/unknown")->assertForbidden();

    $signed = URL::temporarySignedRoute('resumes.measure', now()->addMinute(), ['resume' => $this->resume->slug, 'token' => 'gone'], absolute: false);

    $this->get($signed)->assertNotFound();
});

test('a resume that cannot be measured tells the assistant to estimate instead', function (): void {
    Process::fake(fn (): ProcessResult => Process::result('<html></html>'));

    $result = new MeasureResume(resolve(MeasureResumeAction::class), resolve(ResumeChange::class))
        ->handle(new Request(['resume_id' => $this->resume->id]));

    expect($result)->toContain('could not be measured');
});

test('a change to the resume being edited is compared with the unsaved draft, measured, and loads into the editor', function (): void {
    fakeMeasuringBrowser(2, 0.1);
    $editor = editorContext($this->resume, ['template' => 'compact']);
    $propose = fn (array $arguments): string => new ProposeResume(resolve(AssistantTurn::class), resolve(ResumeChange::class), $editor, resolve(MeasureResumeAction::class))
        ->handle(new Request(['resume_id' => $this->resume->id, 'summary' => 'Shorter', ...$arguments]));

    expect($propose(['template' => 'compact']))->toContain('nothing to propose');

    expect($propose(['template' => 'sidebar']))
        ->toContain('load it into the editor')
        ->toContain('lays out on 2 pages, the last one about 10% full')
        ->and(AssistantProposal::query()->sole())
        ->before->toBe(['template' => 'compact'])
        ->changes->toBe(['template' => 'sidebar']);
});

test('a proposal meant for a length is refused when it measures longer', function (): void {
    fakeMeasuringBrowser(2, 0.15);
    $editor = editorContext($this->resume);

    $result = new ProposeResume(resolve(AssistantTurn::class), resolve(ResumeChange::class), $editor, resolve(MeasureResumeAction::class))
        ->handle(new Request(['resume_id' => $this->resume->id, 'summary' => 'One page', 'template' => 'compact', 'fits_pages' => 1]));

    expect($result)->toContain('not proposed: with these changes it lays out on 2 pages, the last one about 15% full, more than 1 page')
        ->and(AssistantProposal::query()->count())->toBe(0);
});

test('the assistant sees the resume as the editor shows it, with its preview length', function (): void {
    $editor = editorContext($this->resume, [
        'selection' => ['experience' => [['id' => $this->junior->id, 'included' => true], ['id' => $this->lead->id, 'included' => false]]],
        'overrides' => ['summary' => 'An unsaved summary.'],
    ], pages: 2, fill: 0.4);

    expect(resolve(JobHuntAssistant::class)->editingResume($editor)->instructions())
        ->toContain('working on the resume "Backend" (id ' . $this->resume->id . ')')
        ->toContain('An unsaved summary.')
        ->toContain("Experience (id, in order):\n  [x] {$this->junior->id} Junior — Globex\n  [ ] {$this->lead->id} Lead — Acme")
        ->toContain('on 2 A4 pages, the last one about 40% full')
        ->toContain('check it with MeasureResume');
});

test('a message from the editor is about the resume and the job it is tailored for', function (): void {
    JobHuntAssistant::fake(['Here is a shorter version.']);
    $job = JobApplication::factory()->create(['company' => 'Initech', 'role' => 'Engineer']);

    $this->actingAs($this->owner)->postJson(route('assistant.messages.store'), [
        'message' => 'Fit this resume on one page.',
        'resume' => $this->resume->id,
        'resume_draft' => ['name' => 'Backend', 'template' => 'compact', 'job_application_id' => $job->id],
        'resume_preview' => ['pages' => 2, 'last_page_fill' => 0.25],
    ])->assertOk()->streamedContent();

    JobHuntAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Template: compact.')
        && str_contains($prompt->agent->instructions(), 'the last one about 25% full')
        && str_contains($prompt->agent->instructions(), 'This conversation is about one job'));
});

test('a message from the editor needs the editor data', function (): void {
    $this->actingAs($this->owner)
        ->postJson(route('assistant.messages.store'), ['message' => 'Fit it.', 'resume' => $this->resume->id, 'resume_draft' => ['template' => 'nope']])
        ->assertJsonValidationErrors(['resume_draft.name', 'resume_draft.template']);
});

test('the editor loads its assistant conversation on request, with the changes of each resume card', function (): void {
    $conversation = Conversation::query()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-eeeeeeeeeeee',
        'participant_type' => $this->owner->getMorphClass(),
        'participant_id' => $this->owner->id,
        'title' => 'Backend resume',
    ]);
    $message = $conversation->messages()->forceCreate([
        'id' => '01968b4e-8f6a-7b3c-9d2e-ffffffffffff', 'agent' => JobHuntAssistant::class, 'role' => 'assistant', 'content' => 'Try the sidebar.',
        'attachments' => [], 'steps' => [], 'usage' => [], 'meta' => [], 'status' => 'completed',
    ]);
    AssistantProposal::factory()->create([
        'section' => ResumeChange::SECTION,
        'item_id' => $this->resume->id,
        'changes' => ['template' => 'sidebar'],
        'conversation_id' => $conversation->id,
        'message_id' => $message->id,
    ]);

    $this->actingAs($this->owner)
        ->get(route('resumes.edit', ['resume' => $this->resume, 'conversation' => $conversation->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('assistant.isReady', true)
            ->missing('assistantConversation')
            ->reloadOnly('assistantConversation', fn (Assert $reload) => $reload
                ->where('assistantConversation.id', $conversation->id)
                ->where('assistantConversation.messages.0.proposals.0.resume_id', $this->resume->id)
                ->where('assistantConversation.messages.0.proposals.0.changes', ['template' => 'sidebar'])));
});

test('the assistant rewords one part of a resume from the editor text, for the job it is tailored for', function (): void {
    ResumeRewriter::fake([['lines' => ['Senior Engineer — Payments at scale']]]);
    $job = JobApplication::factory()->create(['company' => 'Initech', 'role' => 'Payments Engineer']);

    $this->actingAs($this->owner)
        ->postJson(route('resumes.reword', $this->resume), ['part' => 'headline', 'current' => 'My unsaved headline', 'job_application_id' => $job->id])
        ->assertOk()
        ->assertExactJson(['lines' => ['Senior Engineer — Payments at scale']]);

    ResumeRewriter::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, "On the resume now:\nMy unsaved headline")
        && str_contains($prompt->prompt, "Portfolio text it comes from:\nEngineer — Backend")
        && str_contains($prompt->prompt, 'Payments Engineer at Initech'));
    expect($this->resume->fresh()->textOverride('headline'))->toBeNull();
});

test('a role is reworded from its own facts, one line per highlight', function (): void {
    ResumeRewriter::fake([['lines' => ['Led five engineers on billing', ' ', 'Cut checkout time']]]);

    $this->actingAs($this->owner)
        ->postJson(route('resumes.reword', $this->resume), ['part' => 'highlights', 'experience_id' => $this->lead->id])
        ->assertOk()
        ->assertExactJson(['lines' => ['Led five engineers on billing', 'Cut checkout time']]);

    ResumeRewriter::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, 'The highlights of the role Lead at Acme.')
        && str_contains($prompt->prompt, "Portfolio text it comes from:\nLed a team")
        && ! str_contains($prompt->prompt, 'Junior at Globex'));

    $this->postJson(route('resumes.reword', $this->resume), ['part' => 'highlights'])->assertJsonValidationErrors('experience_id');
});

test('rewording explains why it could not answer', function (): void {
    ResumeRewriter::fake([['lines' => []]]);

    $this->actingAs($this->owner)->postJson(route('resumes.reword', $this->resume), ['part' => 'summary'])
        ->assertStatus(502)
        ->assertJson(['message' => 'The assistant could not reword it. Please try again.']);

    config(['ai.providers.anthropic.key' => null]);

    $this->postJson(route('resumes.reword', $this->resume), ['part' => 'summary'])->assertStatus(409);
});

test('guests cannot reword a resume', function (): void {
    $this->postJson(route('resumes.reword', $this->resume), ['part' => 'summary'])->assertUnauthorized();
});

test('every resume change keeps to the same rules', function (): void {
    foreach ([resolve(JobHuntAssistant::class)->instructions(), new ResumeRewriter()->instructions()] as $instructions) {
        expect($instructions)
            ->toContain('Never add a fact the portfolio does not state')
            ->toContain('whole role only when that is not enough')
            ->toContain("use the job post's own words");
    }
});

test('a conversation started in a resume editor is kept with that resume and listed there', function (): void {
    JobHuntAssistant::fake(['Sure.']);
    $other = Resume::factory()->create();

    $this->actingAs($this->owner)->postJson(route('assistant.messages.store'), [
        'message' => 'Review this resume.',
        'resume' => $this->resume->id,
        'resume_draft' => ['name' => 'Backend', 'template' => 'classic'],
    ])->streamedContent();

    $conversation = App\Models\AssistantConversation::query()->sole();

    expect($conversation->resume_id)->toBe($this->resume->id);

    $this->get(route('resumes.edit', $this->resume))->assertInertia(fn (Assert $page) => $page
        ->where('assistant.conversations.0.id', $conversation->id)
        ->where('opensAssistant', false));
    $this->get(route('resumes.edit', $other))->assertInertia(fn (Assert $page) => $page->where('assistant.conversations', []));
});
