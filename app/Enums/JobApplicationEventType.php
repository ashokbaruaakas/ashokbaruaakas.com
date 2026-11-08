<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum JobApplicationEventType: string
{
    use HasEnumMetadata;

    case StatusChange = 'status_change';
    case Note = 'note';
}
