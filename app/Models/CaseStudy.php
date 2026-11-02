<?php

namespace App\Models;

use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
#[Fillable(['title', 'context', 'problem', 'constraints', 'design', 'tradeoffs', 'results', 'technologies', 'sort_order'])]
class CaseStudy extends Model
{
    /** @use HasFactory<CaseStudyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
