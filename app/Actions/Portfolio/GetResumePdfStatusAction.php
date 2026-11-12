<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumePdfStatus;
use App\Models\Resume;
use App\Models\ResumeBuild;

final readonly class GetResumePdfStatusAction
{
    public function __construct(private GetPortfolioAvailabilityAction $getPortfolioAvailability) {}

    /**
     * Describe what the dashboard should say about a resume's PDF, the public
     * resume's unless another is given. Null means there is nothing to say: the
     * PDF is up to date, or there is no resume to build.
     *
     * @return array{status: string, scheduledFor: string|null}|null
     */
    public function handle(?Resume $resume = null): ?array
    {
        $resume ??= Resume::public();
        $pending = $resume->builds()->whereNot('status', ResumePdfStatus::Ready)->latest('id')->first();

        if ($pending instanceof ResumeBuild) {
            return $this->status($pending->status->value, $pending->scheduled_for->toIso8601String());
        }

        if (! $this->getPortfolioAvailability->handle()->hasResume) {
            return null;
        }

        return $resume->currentBuild() instanceof ResumeBuild ? null : $this->status('missing');
    }

    /**
     * @return array{status: string, scheduledFor: string|null}
     */
    private function status(string $status, ?string $scheduledFor = null): array
    {
        return ['status' => $status, 'scheduledFor' => $scheduledFor];
    }
}
