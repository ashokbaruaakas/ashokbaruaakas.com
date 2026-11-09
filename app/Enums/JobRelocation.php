<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

/**
 * Whether a job helps you move for it. A job whose post does not say has none.
 */
enum JobRelocation: string
{
    use HasEnumMetadata;

    case Offered = 'offered';
    case NotOffered = 'not_offered';
}
