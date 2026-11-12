<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Ai\JobChange;
use App\Ai\ResumeChange;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\ProposalStatus;
use App\Models\AssistantConversation;
use App\Models\AssistantProposal;
use App\Models\CoverLetter;
use App\Models\JobApplication;
use App\Models\Resume;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\ConversationMessage;

final readonly class GetConversationDetailsAction
{
    /**
     * A conversation with its messages, and the proposal and cover letter cards under each reply.
     *
     * @return array<string, mixed>
     */
    public function handle(AssistantConversation $conversation): array
    {
        $proposals = AssistantProposal::query()->where('conversation_id', $conversation->id)->oldest('id')->get();
        $coverLetters = CoverLetter::query()->where('conversation_id', $conversation->id)->oldest('id')->get();

        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'job' => $this->jobSummary($conversation->job),
            'messages' => $conversation->messages()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'role', 'content', 'status', 'created_at'])
                ->map(fn (ConversationMessage $message): array => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'failed' => $message->status === MessageStatus::Failed,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'proposals' => $proposals->where('message_id', $message->id)->map($this->proposalCard(...))->values(),
                    'coverLetters' => $coverLetters->where('message_id', $message->id)->map($this->coverLetterCard(...))->values(),
                ]),
        ];
    }

    /**
     * @return array{id: int, hunt_id: int, hunt: string, company: string, role: string}|null
     */
    public function jobSummary(?JobApplication $job): ?array
    {
        return $job instanceof JobApplication ? [
            'id' => $job->id,
            'hunt_id' => $job->job_hunt_id,
            'hunt' => $job->hunt->title,
            'company' => $job->company,
            'role' => $job->role,
        ] : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalCard(AssistantProposal $proposal): array
    {
        if ($proposal->isForJob()) {
            return $this->jobProposalCard($proposal);
        }

        if ($proposal->addsJob()) {
            return $this->newJobProposalCard($proposal);
        }

        if ($proposal->isForResume()) {
            return $this->resumeProposalCard($proposal);
        }

        $section = $proposal->portfolioSection();
        $fields = $section?->fields() ?? [];
        $titleField = $section?->list?->titleField();
        $itemLabel = $proposal->addsItem()
            ? ($titleField === null ? null : $proposal->changes[$titleField] ?? null)
            : $section?->itemLabel($section->find($proposal->item_id));

        return [
            'id' => $proposal->id,
            'summary' => $proposal->summary,
            'status' => $proposal->status->value,
            'section' => $section?->title(),
            'item' => is_string($itemLabel) ? $itemLabel : null,
            'target' => 'portfolio:' . $proposal->section . ($proposal->item_id === null ? '' : "/{$proposal->item_id}"),
            'adds_item' => $proposal->addsItem(),
            'is_stale' => $proposal->isStale(),
            'fields' => collect($proposal->changes)->map(fn (mixed $value, string $name): array => [
                'name' => $name,
                'label' => ($fields[$name] ?? null) instanceof PortfolioFieldDTO ? $fields[$name]->label : $name,
                'before' => $proposal->before[$name] ?? null,
                'after' => $value,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobProposalCard(AssistantProposal $proposal): array
    {
        $job = $proposal->job();

        return [
            'id' => $proposal->id,
            'summary' => $proposal->summary,
            'status' => $proposal->status->value,
            'section' => 'Job Hunt',
            'item' => $job instanceof JobApplication ? "{$job->company} — {$job->role}" : null,
            'adds_item' => false,
            'is_stale' => $proposal->isStale(),
            'fields' => collect($proposal->changes)->map(fn (mixed $value, string $name): array => [
                'name' => $name,
                'label' => JobChange::label($name),
                'before' => JobChange::display($name, $proposal->before[$name] ?? null),
                'after' => JobChange::display($name, $value),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newJobProposalCard(AssistantProposal $proposal): array
    {
        $hunt = $proposal->hunt();
        $job = $proposal->status === ProposalStatus::Applied && $proposal->created_item_id !== null
            ? JobApplication::query()->find($proposal->created_item_id)
            : null;

        return [
            'id' => $proposal->id,
            'summary' => $proposal->summary,
            'status' => $proposal->status->value,
            'section' => 'Job Hunt',
            'item' => $hunt?->title,
            'adds_item' => true,
            'is_stale' => false,
            'link' => $job instanceof JobApplication ? route('job-hunt.show', [$job->job_hunt_id, 'job' => $job->id]) : null,
            'fields' => collect($proposal->changes)->map(fn (mixed $value, string $name): array => [
                'name' => $name,
                'label' => JobChange::label($name),
                'before' => null,
                'after' => JobChange::display($name, $value),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resumeProposalCard(AssistantProposal $proposal): array
    {
        $addsResume = $proposal->section === ResumeChange::NEW_RESUME_SECTION;
        $resume = Resume::query()->find($addsResume ? $proposal->created_item_id : $proposal->item_id);

        return [
            'id' => $proposal->id,
            'summary' => $proposal->summary,
            'status' => $proposal->status->value,
            'section' => 'Resumes',
            'item' => $resume->name ?? ($addsResume ? ($proposal->changes['name'] ?? null) : null),
            'adds_item' => $addsResume,
            'is_stale' => $proposal->isStale(),
            'link' => $resume instanceof Resume && $proposal->status === ProposalStatus::Applied ? route('resumes.edit', $resume) : null,
            'link_label' => 'Open Resume',
            'resume_id' => $addsResume ? null : $proposal->item_id,
            'changes' => $proposal->changes,
            'preview_url' => $proposal->status === ProposalStatus::Pending ? route('assistant.proposals.preview', $proposal->id) : null,
            'fields' => collect($proposal->changes)->map(fn (mixed $value, string $name): array => [
                'name' => $name,
                'label' => ResumeChange::label($name),
                'before' => ResumeChange::display($name, $proposal->before[$name] ?? null),
                'after' => ResumeChange::display($name, $value) ?? ($name === 'highlights' ? null : 'The portfolio text'),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coverLetterCard(CoverLetter $letter): array
    {
        return [
            'id' => $letter->id,
            'title' => $letter->title,
            'recipient' => $letter->recipient,
            'body' => $letter->body,
            'download_url' => route('assistant.cover-letters.show', $letter->id),
        ];
    }
}
