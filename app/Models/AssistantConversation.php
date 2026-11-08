<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Laravel\Ai\Models\Conversation;

/**
 * A conversation with the assistant, stored by the AI SDK.
 *
 * @property string $id
 * @property string|null $participant_type
 * @property int|null $participant_id
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AssistantConversation extends Conversation {}
