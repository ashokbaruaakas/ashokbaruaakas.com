<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $title
 * @property string $context
 * @property string $problem
 * @property array<int, string> $constraints
 * @property array<int, string> $design
 * @property array<int, string> $tradeoffs
 * @property array<int, string> $results
 * @property array<int, string> $technologies
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class CaseStudy extends Model
{
    /** @use HasFactory<CaseStudyFactory> */
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
            'constraints' => 'array',
            'design' => 'array',
            'tradeoffs' => 'array',
            'results' => 'array',
            'technologies' => 'array',
        ];
    }
}
