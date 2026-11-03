<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PortfolioSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $key
 * @property string|null $eyebrow
 * @property string $title
 * @property string|null $description
 * @property array<string, string>|null $labels
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PortfolioSection extends Model
{
    /** @use HasFactory<PortfolioSectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'labels' => 'array',
        ];
    }
}
