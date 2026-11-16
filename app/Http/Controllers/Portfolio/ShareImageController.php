<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\BuildShareImageAction;
use App\Actions\Portfolio\GetShareCardAction;
use App\DTOs\Portfolio\ShareCardDTO;
use App\Enums\SharePage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class ShareImageController
{
    public function __construct(
        private GetShareCardAction $getShareCard,
        private BuildShareImageAction $buildShareImage,
    ) {}

    /**
     * Serve the page's generated share image. A request for the current
     * fingerprint never changes, so it may be cached for a long time; any other
     * gets the current image with a short lifetime.
     */
    public function show(Request $request, SharePage $page): Response
    {
        $card = $this->getShareCard->handle($page);

        abort_unless($card instanceof ShareCardDTO, 404);

        $isCurrent = $request->query('v') === $card->fingerprint();

        return response($this->buildShareImage->handle($card), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => $isCurrent ? 'public, max-age=31536000, immutable' : 'public, max-age=300',
        ]);
    }
}
