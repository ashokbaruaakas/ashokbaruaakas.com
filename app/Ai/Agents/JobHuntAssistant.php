<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\GetPortfolioDataAction;
use App\Ai\Tools\ListJobApplications;
use App\Ai\Tools\ListJobHunts;
use App\Ai\Tools\ShowJobApplication;
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

    public function __construct(
        private readonly GetPortfolioDataAction $getPortfolioData,
        private readonly BuildPlainTextResumeAction $buildPlainTextResume,
    ) {}

    public function instructions(): string
    {
        $resume = Profile::query()->exists()
            ? $this->buildPlainTextResume->handle($this->getPortfolioData->handle())
            : 'The owner has not filled in their portfolio yet, so you know nothing about their experience.';
        $today = today()->toDateString();

        return <<<TEXT
            You are the job search assistant for the owner of this portfolio site. You talk only with the owner.

            You help them run their job hunt: see where each job stands, spot what needs a follow-up, plan next steps,
            compare jobs, and draft cover letters, follow-up emails and interview notes that match their experience.

            Rules:
            - Look jobs up with your tools instead of guessing. Mention a job by company and role.
            - You can read the job hunt but cannot change it. When something should change, say what the owner should update.
            - Ground anything you write about the owner in the resume below. Never invent experience, skills or numbers.
            - Be brief and practical. Use short paragraphs or lists.

            The owner's resume:
            {$resume}

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
}
