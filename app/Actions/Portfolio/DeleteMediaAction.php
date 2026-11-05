<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\PortfolioMediaSlot;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteMediaAction
{
    public function __construct(private ScheduleResumePdfRebuildAction $scheduleRebuild) {}

    /**
     * Remove the slot's stored files.
     */
    public function handle(PortfolioMediaSlot $slot): void
    {
        foreach (Media::query()->whereIn('key', $slot->keys())->get() as $media) {
            Storage::disk($media->disk)->delete($media->path);

            $media->delete();
        }

        $this->scheduleRebuild->handle();
    }
}
