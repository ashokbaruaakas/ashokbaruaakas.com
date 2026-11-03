<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

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
     * @param  array<int, OpenSourceContributionDTO>  $openSourceContributions
     * @param  array<int, ExperienceDTO>  $experience
     * @param  array<int, EducationDTO>  $education
     * @param  array<int, LanguageDTO>  $languages
     * @param  array<int, string>  $familiarSkills
     * @param  array<int, CaseStudyDTO>  $caseStudies
     * @param  array<int, TestimonialDTO>  $testimonials
     * @param  array<int, string>  $openSourceHighlights
     * @param  array<int, string>  $visibleSections
     * @param  array<int, string>  $alternateNames
     * @param  array<int, string>  $marqueeWords
     * @param  array<int, string>  $knowsAboutTopics
     * @param  array<int, ProfileHighlightDTO>  $highlights
     * @param  array<string, SectionContentDTO>  $sections
     * @param  array<string, PageContentDTO>  $pages
     */
    public function __construct(
        public string $name,
        public string $tagline,
        public string $bio,
        public string $location,
        public string $availability,
        public string $about,
        public string $sideProjects,
        public string $githubUsername,
        public string $organization,
        public string $email,
        public string $phone,
        public array $socialLinks,
        public array $skills,
        public array $projects,
        public array $openSourceContributions,
        public array $experience,
        public array $education,
        public array $languages,
        public array $familiarSkills = [],
        public string $professionalSummary = '',
        public string $websiteUrl = '',
        public array $caseStudies = [],
        public array $testimonials = [],
        public string $howIWork = '',
        public array $openSourceHighlights = [],
        public bool $hasResume = false,
        public bool $hasOpenSource = false,
        public array $visibleSections = [],
        public string $headline = '',
        public array $alternateNames = [],
        public string $city = '',
        public string $country = '',
        public string $timezone = '',
        public string $timezoneLabel = '',
        public string $phoneDisplay = '',
        public string $sourceUrl = '',
        public string $builtWith = '',
        public array $marqueeWords = [],
        public array $knowsAboutTopics = [],
        public string $openSourceInterests = '',
        public array $highlights = [],
        public array $sections = [],
        public array $pages = [],
    ) {}
}
