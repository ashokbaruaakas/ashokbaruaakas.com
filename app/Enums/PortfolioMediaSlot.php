<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum PortfolioMediaSlot: string
{
    use HasEnumMetadata;

    case Portrait = 'portrait';
    case Avatar = 'avatar';
    case Photo = 'photo';
    case ShareImage = 'share-image';
    case FaviconIco = 'favicon-ico';
    case FaviconPng = 'favicon-png';
    case AppleTouchIcon = 'apple-touch-icon';

    public function title(): string
    {
        return match ($this) {
            self::Portrait => 'Portrait',
            self::Avatar => 'Avatar',
            self::Photo => 'Photo',
            self::ShareImage => 'Share image',
            self::FaviconIco => 'Favicon (.ico)',
            self::FaviconPng => 'Favicon (.png)',
            self::AppleTouchIcon => 'Apple touch icon',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Portrait => 'The large image on the home page. Cropped to a square. At least 960 × 960.',
            self::Avatar => 'The small image in the navigation and footer. Cropped to a square. At least 96 × 96.',
            self::Photo => 'Your photo for search engines. At least 400 × 400.',
            self::ShareImage => 'Shown when your pages are shared. PNG, exactly 1200 × 630.',
            self::FaviconIco => 'The browser tab icon, as an ICO file.',
            self::FaviconPng => 'The browser tab icon, as a square PNG. At least 192 × 192.',
            self::AppleTouchIcon => 'The icon on iOS home screens, as a square PNG. At least 180 × 180.',
        };
    }

    /**
     * Get the stored files this slot produces. The first is used as its preview.
     *
     * @return array<int, MediaKey>
     */
    public function keys(): array
    {
        return match ($this) {
            self::Portrait => [MediaKey::Portrait960, MediaKey::Portrait640],
            self::Avatar => [MediaKey::Avatar],
            self::Photo => [MediaKey::Photo],
            self::ShareImage => [MediaKey::ShareImage],
            self::FaviconIco => [MediaKey::FaviconIco],
            self::FaviconPng => [MediaKey::FaviconPng],
            self::AppleTouchIcon => [MediaKey::AppleTouchIcon],
        };
    }

    /**
     * Get the file extensions this slot accepts. SVG is never accepted, because
     * an SVG served from this origin can run scripts.
     *
     * @return array<int, string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::Portrait, self::Avatar, self::Photo => ['jpg', 'jpeg', 'png', 'webp'],
            self::ShareImage, self::FaviconPng, self::AppleTouchIcon => ['png'],
            self::FaviconIco => ['ico'],
        };
    }

    public function minimumSize(): int
    {
        return match ($this) {
            self::Portrait => 960,
            self::Avatar => 96,
            self::Photo => 400,
            self::FaviconPng => 192,
            self::AppleTouchIcon => 180,
            self::ShareImage, self::FaviconIco => 0,
        };
    }
}
