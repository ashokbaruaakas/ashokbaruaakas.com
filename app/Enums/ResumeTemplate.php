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
            self::Classic => 'One column with clear section headings and full links. Fits most resumes on two pages.',
            self::Compact => 'The same single column with tighter spacing, smaller type and contacts on one line. Aimed at one page.',
            self::Sidebar => 'Contact, summary, skills and languages in a left column, experience and projects on the right. Fits a lot on one page.',
        };
    }

    /**
     * Two-column layouts read fine in most applicant tracking systems, but some
     * older ones read across the columns and mix them up.
     */
    public function isAtsSafe(): bool
    {
        return $this !== self::Sidebar;
    }

    public function defaultAccent(): ResumeAccent
    {
        return $this === self::Sidebar ? ResumeAccent::Grey : ResumeAccent::Emerald;
    }

    public function defaultFont(): ResumeFont
    {
        return $this === self::Sidebar ? ResumeFont::Poppins : ResumeFont::Arial;
    }
}
