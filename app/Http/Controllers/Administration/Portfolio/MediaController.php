<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration\Portfolio;

use App\Actions\Portfolio\DeleteMediaAction;
use App\Actions\Portfolio\UploadMediaAction;
use App\DTOs\PageMeta;
use App\Enums\PortfolioMediaSlot;
use App\Http\Requests\Administration\Portfolio\MediaUploadRequest;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Response;

final class MediaController
{
    /**
     * Show the owner's images and files.
     */
    public function index(): Response
    {
        $media = Media::query()->get()->keyBy(fn (Media $media): string => $media->key->value);

        return vue('administration/portfolio/Media', [
            'slots' => array_map(fn (PortfolioMediaSlot $slot): array => [
                'key' => $slot->value,
                'title' => $slot->title(),
                'description' => $slot->description(),
                'accept' => implode(',', array_map(fn (string $extension): string => ".{$extension}", $slot->extensions())),
                'url' => $media->get($slot->keys()[0]->value)?->url(),
            ], PortfolioMediaSlot::cases()),
        ], new PageMeta(
            title: 'Images',
        ));
    }

    /**
     * Replace the file in a slot.
     */
    public function update(MediaUploadRequest $request, PortfolioMediaSlot $slot, UploadMediaAction $action): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $action->handle($slot, $file);

        toastSuccess("{$slot->title()} updated.");

        return back();
    }

    /**
     * Remove the file in a slot.
     */
    public function destroy(PortfolioMediaSlot $slot, DeleteMediaAction $action): RedirectResponse
    {
        $action->handle($slot);

        toastSuccess("{$slot->title()} removed.");

        return back();
    }
}
