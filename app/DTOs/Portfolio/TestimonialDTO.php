<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TestimonialDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $quote,
        public string $author,
        public string $role,
        public string $company,
    ) {}
}
