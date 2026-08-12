<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PortfolioDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public string $name,
        public string $tagline,
        public string $bio,
        public string $location,
        public string $currentWork,
        public string $sideProjects,
        public string $githubUsername,
        public string $organization,
        public array $socialLinks,
        public array $skills,
        public array $projects,
        public array $experience,
    ) {}
}
