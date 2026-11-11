<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResumeTemplate;
use Database\Factories\ResumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * A named configuration over the portfolio that renders to a resume PDF.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ResumeTemplate $template
 * @property int $page_limit
 * @property int|null $page_count
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Resume extends Model
{
    /** @use HasFactory<ResumeFactory> */
    use HasFactory;

    public static function public(): self
    {
        return self::query()->where('is_public', true)->firstOrFail();
    }

    public function isOverPageLimit(): bool
    {
        return $this->page_count !== null && $this->page_count > $this->page_limit;
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
            'template' => ResumeTemplate::class,
            'page_limit' => 'integer',
            'page_count' => 'integer',
            'is_public' => 'boolean',
        ];
    }
}
