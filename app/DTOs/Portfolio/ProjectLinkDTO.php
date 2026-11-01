<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class ProjectLinkDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $label,
        public string $url,
        public bool $isPrimary = false,
    ) {}
}
