<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class SocialLinkDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $platform,
        public string $url,
        public string $icon,
        public ?string $label = null,
        public bool $isPrimary = false,
    ) {}
}
