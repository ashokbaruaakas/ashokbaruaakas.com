<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Enums\ResumePdfStatus;
use App\Models\Media;
use App\Models\ResumePdfBuild;

final readonly class GetResumePdfStatusAction
{
    public function __construct(private GetPortfolioAvailabilityAction $getPortfolioAvailability) {}

    /**
     * Describe what the dashboard should say about the PDF. Null means there is
     * nothing to say: the PDF is up to date, or there is no resume to build it from.
     *
     * @return array{status: string, scheduledFor: string|null}|null
     */
    public function handle(): ?array
    {
        $build = ResumePdfBuild::query()->first();

        if ($build !== null && $build->status !== ResumePdfStatus::Ready) {
            return ['status' => $build->status->value, 'scheduledFor' => $build->scheduled_for->toIso8601String()];
        }

        if (Media::query()->where('key', MediaKey::ResumePdf)->exists() || ! $this->getPortfolioAvailability->handle()->hasResume) {
            return null;
        }

        return ['status' => 'missing', 'scheduledFor' => null];
    }
}
