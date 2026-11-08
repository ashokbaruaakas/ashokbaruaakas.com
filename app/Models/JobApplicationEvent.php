<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use Database\Factories\JobApplicationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * An entry on a job's timeline: a status change or a note.
 *
 * @property int $id
 * @property int $job_application_id
 * @property JobApplicationEventType $type
 * @property JobApplicationStatus|null $from_status
 * @property JobApplicationStatus|null $to_status
 * @property string|null $body
 * @property Carbon $occurred_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobApplication $application
 */
final class JobApplicationEvent extends Model
{
    /** @use HasFactory<JobApplicationEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<JobApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => JobApplicationEventType::class,
            'from_status' => JobApplicationStatus::class,
            'to_status' => JobApplicationStatus::class,
            'occurred_on' => 'date:Y-m-d',
        ];
    }
}
