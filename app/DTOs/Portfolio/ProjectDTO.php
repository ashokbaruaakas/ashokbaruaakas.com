<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class ProjectDTO implements Arrayable, Jsonable
{
    use AsDTO;

    /**
     * @param  array<int, string>  $technologies
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $owner,
        public string $repo,
        public array $technologies,
        public int $stars,
        public string $language,
        public ?string $demoUrl = null,
    ) {}
}
