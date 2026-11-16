<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

/**
 * A public page that gets its own generated share image. Values match the
 * page's route name and its key in page_contents.
 */
enum SharePage: string
{
    use HasEnumMetadata;

    case Home = 'home';
    case Resume = 'resume';
    case OpenSource = 'open-source';

    /**
     * Get the label shown above the name on the page's share image.
     */
    public function label(): ?string
    {
        return match ($this) {
            self::Home => null,
            self::Resume => 'Resume',
            self::OpenSource => 'Open Source',
        };
    }
}
