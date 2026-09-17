<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class OpenSourceContributionDTO implements Arrayable, Jsonable
{
    use AsDTO;

    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $organization,
        public string $type,
        public string $date,
        public array $tags,
        public string $url,
        public ?string $secondaryUrl = null,
        public ?string $metric = null,
    ) {}
}
