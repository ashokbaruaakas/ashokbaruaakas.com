<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeTemplate: string
{
    use HasEnumMetadata;

    case Classic = 'classic';

    public function label(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'One column with clear section headings and full links. Fits most resumes on two pages.',
        };
    }

    public function isAtsSafe(): bool
    {
        return true;
    }
}
