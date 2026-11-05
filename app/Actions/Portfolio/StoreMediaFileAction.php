<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class StoreMediaFileAction
{
    /**
     * Store a file for the key and remove the one it replaces.
     *
     * @throws RuntimeException When the file cannot be written to storage.
     */
    public function handle(MediaKey $key, string $contents, string $extension, string $mimeType, ?string $originalName): Media
    {
        $path = Media::pathFor($key, $contents, $extension);

        throw_unless(Storage::disk('public')->put($path, $contents), RuntimeException::class, 'Unable to store the file.');

        $previousPath = Media::query()->where('key', $key)->value('path');

        $media = Media::query()->updateOrCreate(['key' => $key], [
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => mb_strlen($contents, '8bit'),
            'original_name' => $originalName,
        ]);

        if (is_string($previousPath) && $previousPath !== $path) {
            Storage::disk('public')->delete($previousPath);
        }

        return $media;
    }
}
