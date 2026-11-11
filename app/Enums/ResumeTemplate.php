<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeTemplate: string
{
    use HasEnumMetadata;

    case Classic = 'classic';
    case Compact = 'compact';

    public function label(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'One column with clear section headings and full links. Fits most resumes on two pages.',
            self::Compact => 'The same single column with tighter spacing, smaller type and contacts on one line. Aimed at one page.',
        };
    }

    public function isAtsSafe(): bool
    {
        return true;
    }
}
