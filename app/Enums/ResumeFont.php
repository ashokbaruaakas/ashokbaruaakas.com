<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum ResumeFont: string
{
    use HasEnumMetadata;

    case Arial = 'arial';
    case Poppins = 'poppins';
    case Instrument = 'instrument';

    public function label(): string
    {
        return match ($this) {
            self::Arial => 'Arial',
            self::Poppins => 'Poppins, Name in Playfair Display',
            self::Instrument => 'Instrument Sans',
        };
    }
}
