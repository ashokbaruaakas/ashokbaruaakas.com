<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobHuntOutcome;
use App\Enums\WorkMode;
use Database\Factories\JobHuntFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * One job search, from the day it starts until it ends.
 *
 * @property int $id
 * @property string $title
 * @property string|null $goal
 * @property \Illuminate\Support\Collection<int, WorkMode>|null $work_modes
 * @property string|null $countries
 * @property bool $open_to_relocation
 * @property string|null $notes
 * @property Carbon $started_on
 * @property Carbon|null $ended_on
 * @property JobHuntOutcome|null $outcome
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, JobApplication> $applications
 */
final class JobHunt extends Model
{
    /** @use HasFactory<JobHuntFactory> */
    use HasFactory;

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * The work modes, countries and relocation the owner wants, as the assistant reads them.
     *
     * @return array{work_modes: list<string>, countries: string|null, open_to_relocation: bool}
     */
    public function preferences(): array
    {
        return [
            'work_modes' => array_values($this->work_modes?->map(fn (WorkMode $mode): string => $mode->label())->all() ?? []),
            'countries' => $this->countries,
            'open_to_relocation' => $this->open_to_relocation,
        ];
    }

    public function isActive(): bool
    {
        return $this->ended_on === null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('ended_on');
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
            'started_on' => 'date:Y-m-d',
            'ended_on' => 'date:Y-m-d',
            'outcome' => JobHuntOutcome::class,
            'work_modes' => AsEnumCollection::of(WorkMode::class),
            'open_to_relocation' => 'boolean',
        ];
    }
}
