<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResumePdfStatus;
use Database\Factories\ResumePdfBuildFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * The single row tracking the latest requested rebuild of the resume PDF.
 *
 * @property int $id
 * @property string $token
 * @property ResumePdfStatus $status
 * @property Carbon $scheduled_for
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ResumePdfBuild extends Model
{
    /** @use HasFactory<ResumePdfBuildFactory> */
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
            'status' => ResumePdfStatus::class,
            'scheduled_for' => 'datetime',
        ];
    }
}
