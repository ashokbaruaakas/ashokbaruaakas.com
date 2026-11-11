<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use Database\Factories\ResumeBuildFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * One rendered PDF of a resume, from the moment it is scheduled until it is
 * ready, superseded or failed.
 *
 * @property int $id
 * @property int $resume_id
 * @property string $token
 * @property ResumePdfStatus $status
 * @property Carbon $scheduled_for
 * @property ResumeTemplate|null $template
 * @property int|null $page_count
 * @property string|null $content_hash
 * @property string|null $snapshot
 * @property string|null $path
 * @property Carbon|null $built_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Resume $resume
 * @property-read Collection<int, JobApplication> $sentWith
 */
final class ResumeBuild extends Model
{
    /** @use HasFactory<ResumeBuildFactory> */
    use HasFactory;

    public const string DISK = 'local';

    /**
     * @return BelongsTo<Resume, $this>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function sentWith(): HasMany
    {
        return $this->hasMany(JobApplication::class);
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
            'status' => ResumePdfStatus::class,
            'scheduled_for' => 'datetime',
            'template' => ResumeTemplate::class,
            'page_count' => 'integer',
            'built_at' => 'datetime',
        ];
    }
}
