<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\AssistantTurn;
use App\Ai\PortfolioSection;
use App\Ai\ResumeWriting;
use App\Ai\Tools\DraftCoverLetter;
use App\Ai\Tools\ListJobApplications;
use App\Ai\Tools\ListJobHunts;
use App\Ai\Tools\ProposePortfolioChange;
use App\Ai\Tools\ReadPortfolio;
use App\Ai\Tools\ShowJobApplication;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\Profile;
use App\Models\SkillCategory;
use App\Settings\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Attributes\CacheInstructions;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * The owner's assistant. It reads the portfolio and the job hunt, suggests portfolio changes for the owner to apply,
 * and drafts cover letters. It never changes anything itself.
 */
#[CacheInstructions]
#[MaxSteps(8)]
#[MaxTokens(4096)]
#[Timeout(120)]
final class JobHuntAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    private ?JobApplication $job = null;

    /** @var list<JobApplication> */
    private array $mentionedJobs = [];

    /** @var list<array{section: PortfolioSection, item: Model}> */
    private array $mentionedPortfolio = [];

    public function __construct(
        private readonly AiSettings $settings,
        private readonly AssistantTurn $turn,
    ) {}

    /**
     * Point the assistant at the job the conversation is about and the jobs the latest message mentions.
     * The jobs must have their hunt and events loaded.
     *
     * @param  array<int, JobApplication>  $mentionedJobs
     */
    public function about(?JobApplication $job, array $mentionedJobs = []): static
    {
        $this->job = $job;
        $this->mentionedJobs = array_values(array_filter($mentionedJobs, fn (JobApplication $mentioned): bool => $mentioned->id !== $job?->id));

        return $this;
    }

    /**
     * Show the assistant the portfolio parts the latest message mentions, with their current values.
     *
     * @param  list<array{section: PortfolioSection, item: Model}>  $mentioned
     */
    public function mentioningPortfolio(array $mentioned): static
    {
        $this->mentionedPortfolio = $mentioned;

        return $this;
    }

    public function instructions(): string
    {
        $owner = $this->ownerCard();
        $sections = implode("\n", array_map(
            fn (PortfolioSection $section): string => "- {$section->key}: {$section->description()}",
            PortfolioSection::all(),
        ));
        $notes = filled($this->settings->ownerNotes)
            ? "\nThe owner's own notes and instructions for you, which take priority over your defaults:\n{$this->settings->ownerNotes}\n"
            : '';
        $today = today()->toDateString();
        $jobs = $this->huntContext() . $this->jobContext() . $this->portfolioContext();
        $writing = ResumeWriting::rules();

        return <<<TEXT
            You are the assistant of the owner of this portfolio site. You talk only with the owner.

            You live inside their portfolio site. It has a public portfolio (home page, resume, open-source page and a resume PDF),
            built from the sections below, and a private job hunt tracker where the owner follows every job they apply to.

            You help the owner:
            - keep their portfolio and resume complete and sharp, by discussing it and suggesting changes;
            - run their job hunt: see where each job stands, spot what needs a follow-up, plan next steps and compare jobs;
            - write cover letters, follow-up emails and interview notes that match their real experience.

            Rules:
            - You know only the short summary below. Read the portfolio with ReadPortfolio before you write about the owner's experience,
              projects or skills, judge how they fit a job, or suggest a change. Never invent experience, skills or numbers.
            - Look jobs up with your tools instead of guessing. Mention a job by company and role.
            - Judge every job against what the owner is looking for in its hunt (given below): its work modes, countries, whether
              they would relocate, its goal and notes. Say when a job does not fit, such as an on-site job in another country that
              offers no relocation.
            - You cannot change anything yourself. To change the portfolio, call ProposePortfolioChange; the owner applies it with one click.
              Ask the owner for facts you do not have rather than guessing them. The job hunt is changed only by the owner.
            - When the owner wants a cover letter, write it with DraftCoverLetter so they can download it as a PDF.
            - Be brief and practical. Use short paragraphs or lists.
            - The owner mentions a job as @[Company — Role](job:ID). It means the job with that id; refer to it by company and role.
            - The owner mentions a part of their portfolio as @[Section · Item](portfolio:SECTION/ID), or @[Profile](portfolio:profile).
              Its current values are given below; propose changes to exactly that section and item id.

            {$writing}

            Portfolio sections:
            {$sections}

            The owner in short:
            {$owner}
            {$notes}{$jobs}
            Today is {$today}.
            TEXT;
    }

    public function tools(): iterable
    {
        return [
            new ReadPortfolio(),
            new ProposePortfolioChange($this->turn),
            new DraftCoverLetter($this->turn),
            new ListJobHunts(),
            new ListJobApplications(),
            new ShowJobApplication(),
        ];
    }

    private function ownerCard(): string
    {
        $profile = Profile::query()->first();

        if (! $profile instanceof Profile) {
            return 'The owner has not filled in their portfolio yet, so you know nothing about them.';
        }

        $currentRole = Experience::query()->where('is_current', true)->orderBy('sort_order')->first();
        $skills = SkillCategory::query()->orderBy('sort_order')->get()
            ->map(fn (SkillCategory $category): string => "{$category->name}: " . implode(', ', $category->items))
            ->implode('; ');

        return collect([
            'Name' => $profile->name,
            'Headline' => $profile->headline,
            'Location' => collect([$profile->location, $profile->timezone_label])->filter()->implode(', '),
            'Availability' => $profile->availability,
            'Current role' => $currentRole instanceof Experience ? "{$currentRole->role} at {$currentRole->company}" : null,
            'Skills' => $skills,
            'Summary' => $profile->professional_summary,
        ])->filter(fn (?string $value): bool => filled($value))
            ->map(fn (string $value, string $label): string => "{$label}: {$value}")
            ->implode("\n");
    }

    private function portfolioContext(): string
    {
        if ($this->mentionedPortfolio === []) {
            return '';
        }

        $mentioned = array_map(fn (array $mention): array => [
            'section' => $mention['section']->key,
            'values' => $mention['section']->isList() ? $mention['section']->readItem($mention['item']) : $mention['section']->read(),
        ], $this->mentionedPortfolio);

        return "\nThe parts of the portfolio the owner mentions in their latest message, as saved right now:\n" . $this->encode($mentioned) . "\n";
    }

    private function huntContext(): string
    {
        $hunts = collect([
            JobHunt::query()->active()->latest('started_on')->first(),
            $this->job?->hunt,
            ...array_map(fn (JobApplication $mentioned): JobHunt => $mentioned->hunt, $this->mentionedJobs),
        ])->filter()->unique('id')->values();

        if ($hunts->isEmpty()) {
            return '';
        }

        $details = $hunts->map(fn (JobHunt $hunt): array => [
            'title' => $hunt->title,
            'is_active' => $hunt->isActive(),
            'started_on' => $hunt->started_on->toDateString(),
            'ended_on' => $hunt->ended_on?->toDateString(),
            'looking_for' => $hunt->goal,
            ...$hunt->preferences(),
            'notes' => $hunt->notes,
        ])->all();

        return "\nThe running job hunt and the hunts of the jobs below, with what the owner is looking for in each:\n" . $this->encode($details) . "\n";
    }

    private function jobContext(): string
    {
        $context = '';

        if ($this->job instanceof JobApplication) {
            $context .= "\nThis conversation is about one job. Unless the owner says otherwise, every question is about it:\n"
                . $this->encode(ShowJobApplication::details($this->job)) . "\n";
        }

        if ($this->mentionedJobs !== []) {
            $context .= "\nThe jobs the owner mentions in their latest message:\n"
                . $this->encode(array_map(ShowJobApplication::details(...), $this->mentionedJobs)) . "\n";
        }

        return $context;
    }

    /**
     * @param  array<mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
