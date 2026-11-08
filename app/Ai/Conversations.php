<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\AssistantConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Models\Conversation;

final class Conversations
{
    /**
     * The assistant conversations the user takes part in.
     *
     * @return Builder<AssistantConversation>
     */
    public static function of(User $user): Builder
    {
        return AssistantConversation::query()
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', $user->getKey());
    }
}
