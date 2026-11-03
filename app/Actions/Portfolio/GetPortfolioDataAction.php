<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\CaseStudyDTO;
use App\DTOs\Portfolio\EducationDTO;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\LanguageDTO;
use App\DTOs\Portfolio\OpenSourceContributionDTO;
use App\DTOs\Portfolio\PageContentDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProfileHighlightDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\DTOs\Portfolio\ProjectLinkDTO;
use App\DTOs\Portfolio\SectionContentDTO;
use App\DTOs\Portfolio\SkillCategoryDTO;
use App\DTOs\Portfolio\SocialLinkDTO;
use App\DTOs\Portfolio\TestimonialDTO;
use App\Models\CaseStudy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\OpenSourceContribution;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;

final readonly class GetPortfolioDataAction
{
    private const array SECTION_TITLES = [
        'about' => 'About',
        'skills' => 'Skills',
        'work' => 'Work',
        'case-studies' => 'Case Studies',
        'experience' => 'Experience',
        'education' => 'Education',
        'testimonials' => 'Testimonials',
        'connect' => 'Connect',
        'open-source' => 'Open Source',
    ];

    public function __construct(private GetPortfolioAvailabilityAction $getPortfolioAvailability) {}

    /**
     * Build the portfolio data used by public pages.
     */
    public function handle(): PortfolioDTO
    {
        $profile = Profile::query()->firstOrFail();
        $availability = $this->getPortfolioAvailability->handle();

        $socialLinks = SocialLink::query()->orderBy('sort_order')->get()
            ->map(fn (SocialLink $link): SocialLinkDTO => new SocialLinkDTO(
                platform: $link->platform,
                url: $link->url,
                icon: $link->icon,
                label: $link->label,
                isPrimary: $link->is_primary,
                inStructuredData: $link->in_structured_data,
            ))->all();
        $skills = SkillCategory::query()->orderBy('sort_order')->get()
            ->map(fn (SkillCategory $category): SkillCategoryDTO => new SkillCategoryDTO(
                category: $category->name,
                items: $category->items,
            ))->all();
        $projects = Project::query()->with('links')->orderBy('sort_order')->get()
            ->map(fn (Project $project): ProjectDTO => new ProjectDTO(
                name: $project->name,
                description: $project->description,
                owner: $project->owner,
                repo: $project->repo,
                technologies: $project->technologies,
                stars: $project->stars,
                language: $project->language,
                demoUrl: $project->demo_url,
                tier: $project->tier,
                period: $project->period,
                role: $project->role,
                metric: $project->metric,
                isPublicRepo: $project->is_public_repo,
                linkLabel: $project->link_label,
                showOnResume: $project->show_on_resume,
                links: $project->links
                    ->map(fn (ProjectLink $link): ProjectLinkDTO => new ProjectLinkDTO(
                        label: $link->label,
                        url: $link->url,
                        isPrimary: $link->is_primary,
                    ))->all(),
            ))->all();
        $experience = Experience::query()->orderBy('sort_order')->get()
            ->map(fn (Experience $experience): ExperienceDTO => new ExperienceDTO(
                role: $experience->role,
                company: $experience->company,
                description: $experience->description,
                period: $experience->period,
                url: $experience->url,
                highlights: $experience->highlights,
                isCurrent: $experience->is_current,
            ))->all();
        $education = Education::query()->orderBy('sort_order')->get()
            ->map(fn (Education $education): EducationDTO => new EducationDTO(
                degree: $education->degree,
                school: $education->school,
                period: $education->period,
                institution: $education->institution,
            ))->all();
        $languages = Language::query()->orderBy('sort_order')->get()
            ->map(fn (Language $language): LanguageDTO => new LanguageDTO(
                name: $language->name,
                level: $language->level,
            ))->all();
        $caseStudies = CaseStudy::query()->orderBy('sort_order')->get()
            ->map(fn (CaseStudy $caseStudy): CaseStudyDTO => new CaseStudyDTO(
                title: $caseStudy->title,
                context: $caseStudy->context,
                problem: $caseStudy->problem,
                constraints: $caseStudy->constraints,
                design: $caseStudy->design,
                tradeoffs: $caseStudy->tradeoffs,
                results: $caseStudy->results,
                technologies: $caseStudy->technologies,
            ))->all();
        $testimonials = Testimonial::query()->orderBy('sort_order')->get()
            ->map(fn (Testimonial $testimonial): TestimonialDTO => new TestimonialDTO(
                quote: $testimonial->quote,
                author: $testimonial->author,
                role: $testimonial->role,
                company: $testimonial->company,
            ))->all();

        return new PortfolioDTO(
            name: $profile->name,
            tagline: $profile->tagline ?? '',
            bio: $profile->bio ?? '',
            location: $profile->location ?? '',
            availability: $profile->availability ?? '',
            about: $profile->about ?? '',
            sideProjects: $profile->side_projects ?? '',
            githubUsername: $profile->github_username ?? '',
            organization: $profile->organization ?? '',
            email: $profile->email ?? '',
            phone: $profile->phone ?? '',
            socialLinks: $socialLinks,
            skills: $skills,
            projects: $projects,
            openSourceContributions: OpenSourceContribution::query()->orderBy('sort_order')->get()
                ->map(fn (OpenSourceContribution $contribution): OpenSourceContributionDTO => new OpenSourceContributionDTO(
                    title: $contribution->title,
                    description: $contribution->description,
                    organization: $contribution->organization,
                    type: $contribution->type,
                    date: $contribution->date,
                    tags: $contribution->tags,
                    url: $contribution->url,
                    secondaryUrl: $contribution->secondary_url,
                    metric: $contribution->metric,
                ))->all(),
            experience: $experience,
            education: $education,
            languages: $languages,
            familiarSkills: $profile->familiar_skills ?? [],
            professionalSummary: $profile->professional_summary ?? '',
            websiteUrl: $profile->website_url ?? '',
            caseStudies: $caseStudies,
            testimonials: $testimonials,
            howIWork: $profile->how_i_work ?? '',
            openSourceHighlights: $profile->open_source_highlights ?? [],
            hasResume: $availability->hasResume,
            hasOpenSource: $availability->hasOpenSource,
            visibleSections: $this->visibleSections([
                'hero' => true,
                'about' => filled($profile->about) || filled($profile->how_i_work) || (filled($profile->side_projects) && $availability->hasOpenSource),
                'skills' => $skills !== [],
                'work' => $projects !== [],
                'case-studies' => $caseStudies !== [],
                'experience' => $experience !== [],
                'education' => $education !== [] || $languages !== [],
                'testimonials' => $testimonials !== [],
                'connect' => filled($profile->email) || filled($profile->phone) || $socialLinks !== [],
            ]),
            headline: $profile->headline ?? '',
            alternateNames: $profile->alternate_names ?? [],
            city: $profile->city ?? '',
            country: $profile->country ?? '',
            timezone: $profile->timezone ?? '',
            timezoneLabel: $profile->timezone_label ?? '',
            phoneDisplay: $profile->phone_display ?? $profile->phone ?? '',
            sourceUrl: $profile->source_url ?? '',
            builtWith: $profile->built_with ?? '',
            marqueeWords: $profile->marquee_words ?? [],
            knowsAboutTopics: $profile->knows_about_topics ?? [],
            openSourceInterests: $profile->open_source_interests ?? '',
            highlights: ProfileHighlight::query()->orderBy('sort_order')->get()
                ->map(fn (ProfileHighlight $highlight): ProfileHighlightDTO => new ProfileHighlightDTO(
                    label: $highlight->label,
                    value: $highlight->value,
                    detail: $highlight->detail,
                    shortDetail: $highlight->short_detail,
                ))->all(),
            sections: $this->sections(),
            pages: $this->pages($profile),
        );

    }

    /**
     * Get the copy for each section, using its plain name until it is written.
     *
     * @return array<string, SectionContentDTO>
     */
    private function sections(): array
    {
        $sections = PortfolioSection::query()->get()->keyBy('key');

        return collect(self::SECTION_TITLES)
            ->map(function (string $defaultTitle, string $key) use ($sections): SectionContentDTO {
                $section = $sections->get($key);

                return new SectionContentDTO(
                    eyebrow: $section?->eyebrow,
                    title: $section->title ?? $defaultTitle,
                    description: $section?->description,
                    labels: $section->labels ?? [],
                );
            })
            ->all();
    }

    /**
     * Get the title and share text for each public page.
     *
     * @return array<string, PageContentDTO>
     */
    private function pages(Profile $profile): array
    {
        $pages = PageContent::query()->get()->keyBy('key');

        return collect(['home' => $profile->name, 'resume' => 'Resume', 'open-source' => 'Open Source'])
            ->map(function (string $defaultTitle, string $key) use ($pages, $profile): PageContentDTO {
                $page = $pages->get($key);

                return new PageContentDTO(
                    title: $page->title ?? $defaultTitle,
                    description: $page->description ?? '',
                    imageAlt: $page->image_alt ?? $profile->name,
                );
            })
            ->all();
    }

    /**
     * Get the home page sections that have data to show, in page order.
     *
     * @param  array<string, bool>  $sections
     * @return array<int, string>
     */
    private function visibleSections(array $sections): array
    {
        return array_keys(array_filter($sections));
    }
}
