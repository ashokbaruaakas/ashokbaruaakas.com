<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class SkillCategoryDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $category,
        public array $items,
    ) {}
}
