<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class ExperienceDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $role,
        public string $company,
        public string $description,
        public string $period,
        public ?string $url = null,
    ) {}
}
