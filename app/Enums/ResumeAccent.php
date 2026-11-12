<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeAccent: string
{
    use HasEnumMetadata;

    case Emerald = 'emerald';
    case Blue = 'blue';
    case Teal = 'teal';
    case Indigo = 'indigo';
    case Burgundy = 'burgundy';
    case Grey = 'grey';
    case Black = 'black';

    public function label(): string
    {
        return $this->name;
    }

    public function color(): string
    {
        return match ($this) {
            self::Emerald => '#10b981',
            self::Blue => '#2563eb',
            self::Teal => '#0d9488',
            self::Indigo => '#4f46e5',
            self::Burgundy => '#9f1239',
            self::Grey => '#c8c8c8',
            self::Black => '#111111',
        };
    }
}
