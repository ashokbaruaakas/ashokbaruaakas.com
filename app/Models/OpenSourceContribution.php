<?php

namespace App\Models;

use Database\Factories\OpenSourceContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $organization
 * @property string $type
 * @property string $date
 * @property array<int, string> $tags
 * @property string $url
 * @property string|null $secondary_url
 * @property string|null $metric
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'organization', 'type', 'date', 'tags', 'url', 'secondary_url', 'metric', 'sort_order'])]
class OpenSourceContribution extends Model
{
    /** @use HasFactory<OpenSourceContributionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }
}
