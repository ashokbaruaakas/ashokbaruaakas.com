<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string|null $owner
 * @property string|null $repo
 * @property array<int, string> $technologies
 * @property int $stars
 * @property string $language
 * @property string|null $demo_url
 * @property int $tier
 * @property string|null $period
 * @property string|null $role
 * @property string|null $metric
 * @property bool $is_public_repo
 * @property string|null $link_label
 * @property bool $show_on_resume
 * @property bool $is_visible
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProjectLink> $links
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return HasMany<ProjectLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(ProjectLink::class)->orderBy('sort_order');
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
            'technologies' => 'array',
            'stars' => 'integer',
            'tier' => 'integer',
            'is_public_repo' => 'boolean',
            'show_on_resume' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }
}
