<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\MediaKey;
use App\Enums\ResumePdfStatus;
use App\Models\Media;
use App\Models\Resume;
use App\Models\ResumePdfBuild;

final readonly class GetResumePdfStatusAction
{
    public function __construct(private GetPortfolioAvailabilityAction $getPortfolioAvailability) {}

    /**
     * Describe what the dashboard should say about the PDF. Null means there is
     * nothing to say: the PDF is up to date and within the page limit, or there
     * is no resume to build it from.
     *
     * @return array{status: string, scheduledFor: string|null, pageCount: int|null, pageLimit: int}|null
     */
    public function handle(): ?array
    {
        $resume = Resume::public();
        $build = ResumePdfBuild::query()->first();

        if ($build !== null && $build->status !== ResumePdfStatus::Ready) {
            return $this->status($resume, $build->status->value, $build->scheduled_for->toIso8601String());
        }

        if (! $this->getPortfolioAvailability->handle()->hasResume) {
            return null;
        }

        if (! Media::query()->where('key', MediaKey::ResumePdf)->exists()) {
            return $this->status($resume, 'missing');
        }

        return $resume->isOverPageLimit() ? $this->status($resume, 'over_page_limit') : null;
    }

    /**
     * @return array{status: string, scheduledFor: string|null, pageCount: int|null, pageLimit: int}
     */
    private function status(Resume $resume, string $status, ?string $scheduledFor = null): array
    {
        return ['status' => $status, 'scheduledFor' => $scheduledFor, 'pageCount' => $resume->page_count, 'pageLimit' => $resume->page_limit];
    }
}
