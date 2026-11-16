<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\ShareCardDTO;
use App\DTOs\Portfolio\ShareImageDTO;
use App\Enums\SharePage;

final readonly class GetShareImageAction
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    public function __construct(private GetShareCardAction $getShareCard) {}

    /**
     * Get the image shown when the page is shared: the uploaded share image when
     * the owner set one, otherwise the page's generated card. The card's URL
     * carries its fingerprint, so editing the content changes the URL and makes
     * link previews fetch the new image. Uploads are always stored as PNG.
     */
    public function handle(SharePage $page, ?string $uploadedUrl): ?ShareImageDTO
    {
        if ($uploadedUrl !== null) {
            return new ShareImageDTO($uploadedUrl, self::WIDTH, self::HEIGHT, 'image/png');
        }

        $card = $this->getShareCard->handle($page);

        if (! $card instanceof ShareCardDTO) {
            return null;
        }

        return new ShareImageDTO(
            url: route('share-image', ['page' => $page, 'v' => $card->fingerprint()]),
            width: self::WIDTH,
            height: self::HEIGHT,
            type: 'image/png',
        );
    }
}
