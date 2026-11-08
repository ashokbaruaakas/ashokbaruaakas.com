<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Ai\Models\Conversation;

/**
 * A conversation with the assistant, stored by the AI SDK. It can be about one job.
 *
 * @property string $id
 * @property string|null $participant_type
 * @property int|null $participant_id
 * @property int|null $job_application_id
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobApplication|null $job
 */
final class AssistantConversation extends Conversation
{
    /**
     * The job the conversation is about.
     *
     * @return BelongsTo<JobApplication, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}
