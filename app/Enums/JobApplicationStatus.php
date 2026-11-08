<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum JobApplicationStatus: string
{
    use HasEnumMetadata;

    case Wishlist = 'wishlist';
    case Applied = 'applied';
    case Interviewing = 'interviewing';
    case Offer = 'offer';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Rejected = 'rejected';
    case NoResponse = 'no_response';
    case Withdrawn = 'withdrawn';
    case Ignored = 'ignored';

    /**
     * @return array<int, array{value: string, label: string, stage: string}>
     */
    public static function choices(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
            'stage' => $status->stage()->value,
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::NoResponse => 'No Response',
            default => $this->name,
        };
    }

    public function stage(): JobApplicationStage
    {
        return match ($this) {
            self::Wishlist => JobApplicationStage::ToApply,
            self::Applied, self::Interviewing, self::Offer => JobApplicationStage::InProgress,
            default => JobApplicationStage::Closed,
        };
    }

    /**
     * Whether a job in this status has been applied to. Ignored jobs never were.
     */
    public function hasApplied(): bool
    {
        return ! in_array($this, [self::Wishlist, self::Ignored], true);
    }
}
