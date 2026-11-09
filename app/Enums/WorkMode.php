<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum WorkMode: string
{
    use HasEnumMetadata;

    case OnSite = 'on_site';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::OnSite => 'On-Site',
            default => $this->name,
        };
    }
}
