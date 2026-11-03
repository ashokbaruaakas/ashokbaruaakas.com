<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Enums\PortfolioMediaSlot;
use GdImage;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final readonly class UploadMediaAction
{
    public function __construct(private StoreMediaFileAction $storeMediaFile) {}

    /**
     * Process an upload for the slot and store the files it produces. Images are
     * re-encoded, which also drops anything embedded in the original file.
     *
     * @throws RuntimeException When the image cannot be processed.
     */
    public function handle(PortfolioMediaSlot $slot, UploadedFile $file): void
    {
        $originalName = $file->getClientOriginalName();

        if ($slot === PortfolioMediaSlot::FaviconIco) {
            $this->storeMediaFile->handle(MediaKey::FaviconIco, $file->getContent(), 'ico', 'image/vnd.microsoft.icon', $originalName);

            return;
        }

        $image = imagecreatefromstring($file->getContent());

        throw_unless($image instanceof GdImage, RuntimeException::class, 'Unable to read the image.');

        match ($slot) {
            PortfolioMediaSlot::Portrait => $this->storeSquares($image, [MediaKey::Portrait960->value => 960, MediaKey::Portrait640->value => 640], 'webp', $originalName),
            PortfolioMediaSlot::Avatar => $this->storeSquares($image, [MediaKey::Avatar->value => 96], 'webp', $originalName),
            PortfolioMediaSlot::Photo => $this->storeMediaFile->handle(MediaKey::Photo, $this->encode($this->fit($image, 1200), 'jpeg'), 'jpg', 'image/jpeg', $originalName),
            PortfolioMediaSlot::ShareImage => $this->storeMediaFile->handle(MediaKey::ShareImage, $this->encode($image, 'png'), 'png', 'image/png', $originalName),
            PortfolioMediaSlot::FaviconPng => $this->storeSquares($image, [MediaKey::FaviconPng->value => 192], 'png', $originalName),
            PortfolioMediaSlot::AppleTouchIcon => $this->storeSquares($image, [MediaKey::AppleTouchIcon->value => 180], 'png', $originalName),
        };
    }

    /**
     * @param  array<string, int<1, max>>  $sizes
     */
    private function storeSquares(GdImage $image, array $sizes, string $format, string $originalName): void
    {
        foreach ($sizes as $key => $size) {
            $this->storeMediaFile->handle(MediaKey::from($key), $this->encode($this->square($image, $size), $format), $format, "image/{$format}", $originalName);
        }
    }

    /**
     * Crop the center of the image to a square of the given size.
     *
     * @param  int<1, max>  $size
     */
    private function square(GdImage $image, int $size): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        return $this->copy($image, (int) (($width - $side) / 2), (int) (($height - $side) / 2), $side, $side, $size, $size);
    }

    /**
     * Shrink the image so its longest side fits, keeping its proportions.
     */
    private function fit(GdImage $image, int $longestSide): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $longestSide / max($width, $height));

        return $this->copy($image, 0, 0, $width, $height, max(1, (int) ($width * $scale)), max(1, (int) ($height * $scale)));
    }

    /**
     * @param  int<1, max>  $targetWidth
     * @param  int<1, max>  $targetHeight
     */
    private function copy(GdImage $image, int $x, int $y, int $width, int $height, int $targetWidth, int $targetHeight): GdImage
    {
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $image, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    private function encode(GdImage $image, string $format): string
    {
        ob_start();

        match ($format) {
            'webp' => imagewebp($image, null, 85),
            'jpeg' => imagejpeg($image, null, 85),
            default => imagepng($image),
        };

        $contents = ob_get_clean();

        throw_unless(is_string($contents) && $contents !== '', RuntimeException::class, 'Unable to encode the image.');

        return $contents;
    }
}
