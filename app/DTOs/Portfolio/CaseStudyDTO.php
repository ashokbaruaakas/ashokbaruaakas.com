<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class CaseStudyDTO implements Arrayable, Jsonable
{
    use AsDTO;

    /**
     * @param  array<int, string>  $constraints
     * @param  array<int, string>  $design
     * @param  array<int, string>  $tradeoffs
     * @param  array<int, string>  $results
     * @param  array<int, string>  $technologies
     */
    public function __construct(
        public string $title,
        public string $context,
        public string $problem,
        public array $constraints,
        public array $design,
        public array $tradeoffs,
        public array $results,
        public array $technologies,
    ) {}
}
