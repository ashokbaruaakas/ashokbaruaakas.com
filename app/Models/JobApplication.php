<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobApplicationStatus;
use App\Enums\JobChance;
use App\Enums\JobRelocation;
use App\Enums\WorkMode;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * A job within a hunt, whether it is still on the wishlist, applied to or closed.
 *
 * @property int $id
 * @property int $job_hunt_id
 * @property string $company
 * @property string $role
 * @property JobApplicationStatus $status
 * @property JobChance|null $chance
 * @property string|null $chance_note
 * @property string|null $link
 * @property string|null $country
 * @property string|null $location
 * @property WorkMode|null $work_mode
 * @property JobRelocation|null $relocation
 * @property string|null $source
 * @property string|null $salary
 * @property string|null $contact
 * @property Carbon|null $applied_on
 * @property Carbon|null $deadline
 * @property string|null $next_step
 * @property Carbon|null $next_step_on
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobHunt $hunt
 * @property-read Collection<int, JobApplicationEvent> $events
 */
final class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    public const int FOLLOW_UP_AFTER_DAYS = 10;

    /**
     * The fields the owner edits directly. Status changes go through their own action so they reach the timeline.
     */
    public const array EDITABLE_ATTRIBUTES = [
        'company', 'role', 'chance', 'chance_note', 'link', 'country', 'location', 'work_mode', 'relocation', 'source',
        'salary', 'contact', 'applied_on', 'deadline', 'next_step', 'next_step_on', 'notes',
    ];

    /**
     * @return BelongsTo<JobHunt, $this>
     */
    public function hunt(): BelongsTo
    {
        return $this->belongsTo(JobHunt::class, 'job_hunt_id');
    }

    /**
     * @return HasMany<JobApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(JobApplicationEvent::class)->latest('occurred_on')->latest('id');
    }

    /**
     * Whether the job was applied to a while ago and nothing has happened since. Needs `events` loaded.
     */
    public function needsFollowUp(): bool
    {
        if ($this->status !== JobApplicationStatus::Applied) {
            return false;
        }

        $lastActivity = $this->applied_on;

        foreach ($this->events as $event) {
            if ($lastActivity === null || $event->occurred_on->gt($lastActivity)) {
                $lastActivity = $event->occurred_on;
            }
        }

        return $lastActivity !== null && $lastActivity->lte(today()->subDays(self::FOLLOW_UP_AFTER_DAYS));
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
            'status' => JobApplicationStatus::class,
            'chance' => JobChance::class,
            'work_mode' => WorkMode::class,
            'relocation' => JobRelocation::class,
            'applied_on' => 'date:Y-m-d',
            'deadline' => 'date:Y-m-d',
            'next_step_on' => 'date:Y-m-d',
        ];
    }
}
