<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\AssistantProposal;
use App\Models\CoverLetter;
use Laravel\Ai\Models\ConversationMessage;

/**
 * Collects what the assistant's tools create during one turn, so it can be tied to the reply once the reply is stored.
 */
final class AssistantTurn
{
    /** @var list<int> */
    private array $proposalIds = [];

    /** @var list<int> */
    private array $coverLetterIds = [];

    public function created(AssistantProposal|CoverLetter $record): void
    {
        if ($record instanceof AssistantProposal) {
            $this->proposalIds[] = $record->id;
        } else {
            $this->coverLetterIds[] = $record->id;
        }
    }

    /**
     * Tie everything created this turn to the conversation and the reply that holds it.
     */
    public function attachTo(?string $conversationId, ?string $messageId): void
    {
        $attributes = ['conversation_id' => $conversationId, 'message_id' => $messageId];

        if ($this->proposalIds !== []) {
            AssistantProposal::query()->whereKey($this->proposalIds)->update($attributes);
        }

        if ($this->coverLetterIds !== []) {
            CoverLetter::query()->whereKey($this->coverLetterIds)->update($attributes);
        }

        $this->proposalIds = [];
        $this->coverLetterIds = [];
    }

    /**
     * Tie everything created this turn to the conversation's latest reply, for a reply that failed after its tools ran
     * and was stored without handing back its id.
     */
    public function attachToLatestReply(?string $conversationId): void
    {
        if ($conversationId === null || ($this->proposalIds === [] && $this->coverLetterIds === [])) {
            return;
        }

        $reply = ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->latest()
            ->latest('id')
            ->first();

        $this->attachTo($conversationId, $reply?->id);
    }
}
