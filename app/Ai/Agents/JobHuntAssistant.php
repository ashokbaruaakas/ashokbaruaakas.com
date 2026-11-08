<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\GetPortfolioDataAction;
use App\Ai\Tools\ListJobApplications;
use App\Ai\Tools\ListJobHunts;
use App\Ai\Tools\ShowJobApplication;
use App\Models\JobApplication;
use App\Models\Profile;
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
 * The owner's assistant for their job search. It can read every hunt and job, but cannot change them.
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

    public function __construct(
        private readonly GetPortfolioDataAction $getPortfolioData,
        private readonly BuildPlainTextResumeAction $buildPlainTextResume,
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

    public function instructions(): string
    {
        $resume = Profile::query()->exists()
            ? $this->buildPlainTextResume->handle($this->getPortfolioData->handle())
            : 'The owner has not filled in their portfolio yet, so you know nothing about their experience.';
        $today = today()->toDateString();
        $jobs = $this->jobContext();

        return <<<TEXT
            You are the job search assistant for the owner of this portfolio site. You talk only with the owner.

            You help them run their job hunt: see where each job stands, spot what needs a follow-up, plan next steps,
            compare jobs, and draft cover letters, follow-up emails and interview notes that match their experience.

            Rules:
            - Look jobs up with your tools instead of guessing. Mention a job by company and role.
            - You can read the job hunt but cannot change it. When something should change, say what the owner should update.
            - Ground anything you write about the owner in the resume below. Never invent experience, skills or numbers.
            - Be brief and practical. Use short paragraphs or lists.
            - The owner mentions a job as @[Company — Role](job:ID). It means the job with that id; refer to it by company and role.

            The owner's resume:
            {$resume}
            {$jobs}

            Today is {$today}.
            TEXT;
    }

    public function tools(): iterable
    {
        return [
            new ListJobHunts(),
            new ListJobApplications(),
            new ShowJobApplication(),
        ];
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
