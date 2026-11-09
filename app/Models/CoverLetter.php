<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CoverLetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A cover letter the assistant drafted, which the owner downloads as a PDF.
 *
 * @property int $id
 * @property string|null $conversation_id
 * @property string|null $message_id
 * @property int|null $job_application_id
 * @property string $title
 * @property string|null $recipient
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobApplication|null $job
 */
final class CoverLetter extends Model
{
    /** @use HasFactory<CoverLetterFactory> */
    use HasFactory;

    /**
     * The job the letter is for.
     *
     * @return BelongsTo<JobApplication, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /**
     * The letter's paragraphs, split on blank lines.
     *
     * @return list<string>
     */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R\s*\R/', $this->body) ?: []), fn (string $paragraph): bool => $paragraph !== ''));
    }
}
