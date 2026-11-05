<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumePdfStatus: string
{
    use HasEnumMetadata;

    case Scheduled = 'scheduled';
    case Building = 'building';
    case Ready = 'ready';
    case Failed = 'failed';
}
