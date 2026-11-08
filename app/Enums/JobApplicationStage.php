<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum JobApplicationStage: string
{
    use HasEnumMetadata;

    case ToApply = 'to_apply';
    case InProgress = 'in_progress';
    case Closed = 'closed';
}
