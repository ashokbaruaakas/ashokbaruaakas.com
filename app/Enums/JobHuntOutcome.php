<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum JobHuntOutcome: string
{
    use HasEnumMetadata;

    case Hired = 'hired';
    case Paused = 'paused';
    case Abandoned = 'abandoned';
}
