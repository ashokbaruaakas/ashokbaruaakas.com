<?php

declare(strict_types=1);

namespace App\Enums;

use SoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum SkillCategory: int
{
    use HasEnumMetadata;

    case Languages = 1;
    case Frameworks = 2;
    case Tools = 3;
    case DevOps = 4;
    case AiAgents = 5;
}
