<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeTemplate: string
{
    use HasEnumMetadata;

    case Classic = 'classic';
    case Compact = 'compact';
    case Sidebar = 'sidebar';

    public function label(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'One column with clear headings. Fits most resumes on two pages.',
            self::Compact => 'One column with tighter spacing and smaller type. Aims at one page.',
            self::Sidebar => 'Contact, summary and skills on the left, experience and projects on the right.',
        };
    }

    public function defaultFont(): ResumeFont
    {
        return $this === self::Sidebar ? ResumeFont::Poppins : ResumeFont::Arial;
    }
}
