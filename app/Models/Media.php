<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaKey;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

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
final class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * Get where a file is stored. Names include a content hash because nginx
     * serves images as immutable, so a replaced file needs a new URL.
     */
    public static function pathFor(MediaKey $key, string $contents, string $extension): string
    {
        $hash = mb_substr(hash('sha256', $contents), 0, 16);

        return "owner/{$key->value}-{$hash}.{$extension}";
    }

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
    #[Override]
    protected function casts(): array
    {
        return [
            'key' => MediaKey::class,
            'size' => 'integer',
        ];
    }
}
