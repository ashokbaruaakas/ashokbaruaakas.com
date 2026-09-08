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

    /**
     * @param  array<int, SocialLinkDTO>  $socialLinks
     * @param  array<int, SkillCategoryDTO>  $skills
     * @param  array<int, ProjectDTO>  $projects
     * @param  array<int, ExperienceDTO>  $experience
     * @param  array<int, EducationDTO>  $education
     * @param  array<int, LanguageDTO>  $languages
     */
    public function __construct(
        public string $name,
        public string $tagline,
        public string $bio,
        public string $location,
        public string $currentWork,
        public string $sideProjects,
        public string $githubUsername,
        public string $organization,
        public string $email,
        public string $phone,
        public array $socialLinks,
        public array $skills,
        public array $projects,
        public array $experience,
        public array $education,
        public array $languages,
    ) {}
}
