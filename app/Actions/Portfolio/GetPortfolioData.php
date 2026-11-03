<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\CaseStudyDTO;
use App\DTOs\Portfolio\EducationDTO;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\LanguageDTO;
use App\DTOs\Portfolio\OpenSourceContributionDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\DTOs\Portfolio\ProjectLinkDTO;
use App\DTOs\Portfolio\SkillCategoryDTO;
use App\DTOs\Portfolio\SocialLinkDTO;
use App\DTOs\Portfolio\TestimonialDTO;
use App\Models\CaseStudy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\OpenSourceContribution;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;

class GetPortfolioData
{
    public function __construct(private GetPortfolioAvailability $getPortfolioAvailability) {}

    /**
     * Build the portfolio data used by public pages.
     */
    public function handle(): PortfolioDTO
    {
        $profile = Profile::query()->firstOrFail();
        $availability = $this->getPortfolioAvailability->handle();

        $portfolio = new PortfolioDTO(
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
            socialLinks: SocialLink::query()->orderBy('sort_order')->get()
                ->map(fn (SocialLink $link): SocialLinkDTO => new SocialLinkDTO(
                    platform: $link->platform,
                    url: $link->url,
                    icon: $link->icon,
                    label: $link->label,
                    isPrimary: $link->is_primary,
                ))->all(),
            skills: SkillCategory::query()->orderBy('sort_order')->get()
                ->map(fn (SkillCategory $category): SkillCategoryDTO => new SkillCategoryDTO(
                    category: $category->name,
                    items: $category->items,
                ))->all(),
            projects: Project::query()->with('links')->orderBy('sort_order')->get()
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
                ))->all(),
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
            experience: Experience::query()->orderBy('sort_order')->get()
                ->map(fn (Experience $experience): ExperienceDTO => new ExperienceDTO(
                    role: $experience->role,
                    company: $experience->company,
                    description: $experience->description,
                    period: $experience->period,
                    url: $experience->url,
                    highlights: $experience->highlights,
                ))->all(),
            education: Education::query()->orderBy('sort_order')->get()
                ->map(fn (Education $education): EducationDTO => new EducationDTO(
                    degree: $education->degree,
                    school: $education->school,
                    period: $education->period,
                ))->all(),
            languages: Language::query()->orderBy('sort_order')->get()
                ->map(fn (Language $language): LanguageDTO => new LanguageDTO(
                    name: $language->name,
                    level: $language->level,
                ))->all(),
            familiarSkills: $profile->familiar_skills ?? [],
            professionalSummary: $profile->professional_summary ?? '',
            websiteUrl: $profile->website_url ?? '',
            caseStudies: CaseStudy::query()->orderBy('sort_order')->get()
                ->map(fn (CaseStudy $caseStudy): CaseStudyDTO => new CaseStudyDTO(
                    title: $caseStudy->title,
                    context: $caseStudy->context,
                    problem: $caseStudy->problem,
                    constraints: $caseStudy->constraints,
                    design: $caseStudy->design,
                    tradeoffs: $caseStudy->tradeoffs,
                    results: $caseStudy->results,
                    technologies: $caseStudy->technologies,
                ))->all(),
            testimonials: Testimonial::query()->orderBy('sort_order')->get()
                ->map(fn (Testimonial $testimonial): TestimonialDTO => new TestimonialDTO(
                    quote: $testimonial->quote,
                    author: $testimonial->author,
                    role: $testimonial->role,
                    company: $testimonial->company,
                ))->all(),
            howIWork: $profile->how_i_work ?? '',
            openSourceHighlights: $profile->open_source_highlights ?? [],
            hasResume: $availability->hasResume,
            hasOpenSource: $availability->hasOpenSource,
        );

        return new PortfolioDTO(...[...get_object_vars($portfolio), 'visibleSections' => $this->visibleSections($portfolio)]);
    }

    /**
     * Get the home page sections that have data to show, in page order.
     *
     * @return array<int, string>
     */
    private function visibleSections(PortfolioDTO $portfolio): array
    {
        return array_keys(array_filter([
            'hero' => true,
            'about' => $portfolio->about !== '' || $portfolio->howIWork !== '' || ($portfolio->sideProjects !== '' && $portfolio->hasOpenSource),
            'skills' => $portfolio->skills !== [],
            'work' => $portfolio->projects !== [],
            'case-studies' => $portfolio->caseStudies !== [],
            'experience' => $portfolio->experience !== [],
            'education' => $portfolio->education !== [] || $portfolio->languages !== [],
            'testimonials' => $portfolio->testimonials !== [],
            'connect' => $portfolio->email !== '' || $portfolio->phone !== '' || $portfolio->socialLinks !== [],
        ]));
    }
}
