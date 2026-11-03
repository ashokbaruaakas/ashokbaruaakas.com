<?php

namespace App\Models;

use App\Enums\MediaKey;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property MediaKey $key
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property string|null $original_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'disk', 'path', 'mime_type', 'size', 'original_name'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * Get the public URL of the file.
     */
    public function url(): string
    {
        return asset("storage/{$this->path}");
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => MediaKey::class,
            'size' => 'integer',
        ];
    }
}
