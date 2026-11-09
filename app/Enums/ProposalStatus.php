<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ProposalStatus: string
{
    use HasEnumMetadata;

    case Pending = 'pending';
    case Applied = 'applied';
    case Dismissed = 'dismissed';
}
