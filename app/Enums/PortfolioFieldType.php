<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum PortfolioFieldType: string
{
    use HasEnumMetadata;

    case Text = 'text';
    case Textarea = 'textarea';
    case Url = 'url';
    case Email = 'email';
    case Number = 'number';
    case Boolean = 'boolean';
    case Select = 'select';
    case Lines = 'lines';
    case Links = 'links';
}
