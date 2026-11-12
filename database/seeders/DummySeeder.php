<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Actions\Portfolio\StoreMediaFileAction;
use App\Enums\MediaKey;
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
use App\Models\Resume;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class DummySeeder extends Seeder
{
    /**
     * Fill an empty local install with a fictional owner, so every page can be
     * reviewed straight away. The account has a known password, so this only
     * runs locally. Existing content is kept, but the name and email owner:create
     * starts the profile with are replaced.
     */
    public function run(GetPortfolioAvailabilityAction $getPortfolioAvailability): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DummySeeder only runs in the local environment.');

            return;
        }

        if ($getPortfolioAvailability->handle()->hasHome) {
            $this->command?->warn('The portfolio already has content, so nothing was seeded.');

            return;
        }

        DB::transaction(function (): void {
            Profile::query()->delete();
            Profile::query()->create($this->profile());

            $this->storeMedia();

            $this->createInOrder(ProfileHighlight::class, $this->profileHighlights());
            $this->createInOrder(SocialLink::class, $this->socialLinks());
            $this->createInOrder(SkillCategory::class, $this->skillCategories());
            $this->createInOrder(OpenSourceContribution::class, $this->openSourceContributions());
            $this->createInOrder(Experience::class, $this->experiences());
            $this->createInOrder(Education::class, $this->education());
            $this->createInOrder(Language::class, $this->languages());
            $this->createInOrder(CaseStudy::class, $this->caseStudies());
            $this->createInOrder(Testimonial::class, $this->testimonials());

            foreach ($this->portfolioSections() as $attributes) {
                PortfolioSection::query()->create($attributes);
            }

            foreach ($this->pageContents() as $attributes) {
                PageContent::query()->create($attributes);
            }

            $resumeProjects = [];

            foreach ($this->projects() as $projectOrder => $attributes) {
                /** @var array<int, array<string, mixed>> $links */
                $links = $attributes['links'] ?? [];
                $isOnResume = (bool) ($attributes['on_resume'] ?? true);
                unset($attributes['links'], $attributes['on_resume']);

                $project = Project::query()->create([...$attributes, 'sort_order' => $projectOrder]);
                $resumeProjects[] = ['id' => $project->id, 'included' => $isOnResume && $project->tier === 2];

                foreach ($links as $linkOrder => $link) {
                    $project->links()->create([...$link, 'sort_order' => $linkOrder]);
                }
            }

            Resume::public()->update(['selection' => ['projects' => $resumeProjects]]);

            if (! User::query()->exists()) {
                User::factory()->create(['name' => 'Sam Rivera', 'email' => 'admin@example.com']);
            }
        });

        $this->command?->info('Sample portfolio ready. Sign in at /login with admin@example.com and the password "password".');
    }

    /**
     * Copy the sample images to storage.
     */
    private function storeMedia(): void
    {
        $files = [
            'portrait-640.webp' => MediaKey::Portrait640,
            'portrait-960.webp' => MediaKey::Portrait960,
            'avatar.webp' => MediaKey::Avatar,
            'photo.jpg' => MediaKey::Photo,
            'share-image.png' => MediaKey::ShareImage,
            'favicon.ico' => MediaKey::FaviconIco,
            'favicon.png' => MediaKey::FaviconPng,
            'apple-touch-icon.png' => MediaKey::AppleTouchIcon,
        ];

        foreach ($files as $fileName => $key) {
            $source = database_path("seeders/dummy/{$fileName}");

            app(StoreMediaFileAction::class)->handle($key, File::get($source), File::extension($source), (string) File::mimeType($source), $fileName);
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function createInOrder(string $model, array $rows): void
    {
        foreach ($rows as $sortOrder => $attributes) {
            $model::query()->create([...$attributes, 'sort_order' => $sortOrder]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(): array
    {
        return [
            'name' => 'Sam Rivera',
            'headline' => 'Senior Full-Stack Engineer',
            'alternate_names' => ['Samuel Rivera'],
            'tagline' => 'Senior Full-Stack Engineer — SaaS & Developer Tools',
            'bio' => 'I build dependable products with **Laravel, TypeScript, and Vue**, from database design and APIs to deployment and monitoring.',
            'location' => 'Lisbon, Portugal',
            'city' => 'Lisbon',
            'country' => 'Portugal',
            'timezone' => 'Europe/Lisbon',
            'timezone_label' => 'UTC+0',
            'availability' => 'Open to remote roles',
            'email' => 'sam@example.com',
            'phone' => '+15550100',
            'phone_display' => '+1 555 0100',
            'website_url' => 'https://example.com',
            'source_url' => 'https://github.com/example/portfolio',
            'github_username' => 'example',
            'organization' => 'example-labs',
            'built_with' => 'Laravel · Inertia · Vue',
            'marquee_words' => ['schema', 'migrate', 'route', 'validate', 'authorize', 'queue', 'retry', 'test', 'deploy', 'observe'],
            'knows_about_topics' => ['PHP', 'TypeScript', 'Laravel', 'Vue.js', 'Inertia.js', 'Tailwind CSS', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'CI/CD'],
            'about' => 'Sam Rivera is a fictional engineer, and everything on this page is sample content. Sam has spent nine years building subscription products and internal tools, most recently leading the billing team at Globex. Sam likes small releases, boring technology, and code that a new teammate can read on their first day.',
            'how_i_work' => 'I write a short plan before I build, ship behind flags in small steps, and keep tests close to the behavior people rely on. Anything touching billing or access gets a second reader.',
            'side_projects' => 'A handful of small Laravel packages and command-line tools, plus occasional fixes to the open-source projects I use every day.',
            'open_source_interests' => 'I enjoy working on Laravel packages, developer tooling, and documentation.',
            'open_source_highlights' => [
                'Maintain Tidy Queue, a dashboard for inspecting failed jobs',
                'Merged documentation and bug fixes in several Laravel packages',
            ],
            'familiar_skills' => ['React', 'Go', 'Terraform'],
            'professional_summary' => 'Senior Full-Stack Engineer with nine years of experience building subscription products in Laravel, Vue and TypeScript. Led a team of five on billing at Globex, moving 40,000 accounts to a new invoicing system without downtime.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function socialLinks(): array
    {
        return [
            ['platform' => 'LinkedIn', 'in_structured_data' => true, 'url' => 'https://www.linkedin.com/in/example', 'icon' => 'linkedin', 'is_primary' => true],
            ['platform' => 'GitHub', 'in_structured_data' => true, 'url' => 'https://github.com/example', 'icon' => 'github', 'is_primary' => true],
            ['platform' => 'X', 'in_structured_data' => true, 'url' => 'https://x.com/example', 'icon' => 'twitter', 'is_primary' => false],
            ['platform' => 'Telegram', 'url' => 'https://t.me/example', 'icon' => 'send', 'is_primary' => false],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function skillCategories(): array
    {
        return [
            ['name' => 'Languages', 'items' => ['PHP', 'TypeScript', 'JavaScript', 'SQL', 'HTML', 'CSS']],
            ['name' => 'Frameworks', 'items' => ['Laravel', 'Vue.js', 'Inertia.js', 'Tailwind CSS', 'Livewire']],
            ['name' => 'Backend & Architecture', 'items' => ['REST API design', 'Queues', 'Webhooks', 'Caching', 'System design']],
            ['name' => 'Data & Infrastructure', 'items' => ['MySQL', 'PostgreSQL', 'Redis', 'Docker', 'GitHub Actions', 'Nginx']],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'name' => 'Globex Billing',
                'description' => 'The subscription and invoicing platform behind a fictional B2B software company, with plan changes, proration, tax handling, and dunning.',
                'technologies' => ['Laravel', 'Vue.js', 'TypeScript', 'PostgreSQL', 'Redis'],
                'stars' => 0,
                'language' => 'PHP',
                'demo_url' => 'https://example.com/globex-billing',
                'tier' => 1,
                'period' => '2022 — Present',
                'role' => 'Lead engineer for a team of five, responsible for architecture and delivery.',
                'metric' => '40,000 accounts migrated',
                'is_public_repo' => false,
                'link_label' => 'Product',
                'on_resume' => true,
            ],
            [
                'name' => 'Initech Field Ops',
                'description' => 'A scheduling and reporting tool for field teams, with offline-friendly forms and a manager dashboard.',
                'technologies' => ['Laravel', 'Livewire', 'MySQL', 'Docker'],
                'stars' => 0,
                'language' => 'PHP',
                'demo_url' => 'https://example.com/initech-field-ops',
                'tier' => 1,
                'period' => '2019 — 2022',
                'role' => 'Built the scheduling engine and the reporting module.',
                'metric' => 'Used by 1,200 staff',
                'is_public_repo' => false,
                'link_label' => 'Company',
                'on_resume' => true,
            ],
            [
                'name' => 'Tidy Queue',
                'description' => 'A small dashboard for finding, grouping, and retrying failed Laravel jobs.',
                'owner' => 'example',
                'repo' => 'tidy-queue',
                'technologies' => ['Laravel', 'Vue.js', 'Redis'],
                'stars' => 128,
                'language' => 'PHP',
                'demo_url' => 'https://example.com/tidy-queue',
                'tier' => 2,
                'metric' => '128 stars',
                'is_public_repo' => true,
                'on_resume' => true,
                'links' => [
                    ['label' => 'Packagist', 'url' => 'https://example.com/packages/tidy-queue', 'is_primary' => false],
                ],
            ],
            [
                'name' => 'Release Notes CLI',
                'description' => 'A command-line tool that drafts release notes from merged pull requests.',
                'technologies' => ['TypeScript', 'GitHub Actions'],
                'stars' => 0,
                'language' => 'TypeScript',
                'tier' => 2,
                'is_public_repo' => true,
                'on_resume' => false,
                'links' => [
                    ['label' => 'Source', 'url' => 'https://github.com/example/release-notes-cli', 'is_primary' => true],
                    ['label' => 'Documentation', 'url' => 'https://example.com/release-notes-cli', 'is_primary' => false],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function openSourceContributions(): array
    {
        return [
            [
                'title' => 'Tidy Queue',
                'description' => 'Built and published a dashboard for finding, grouping, and retrying failed Laravel jobs.',
                'organization' => 'Sam Rivera',
                'type' => 'Project',
                'date' => 'March 12, 2026',
                'tags' => ['Projects', 'Laravel', 'Developer Tools'],
                'url' => 'https://github.com/example/tidy-queue',
                'secondary_url' => 'https://example.com/packages/tidy-queue',
            ],
            [
                'title' => 'Fix pagination in a sample package',
                'description' => 'Fixed an off-by-one error that hid the last page of results when filters were applied.',
                'organization' => 'Example Labs',
                'type' => 'Pull Request',
                'date' => 'November 3, 2025',
                'tags' => ['Pull Requests', 'Laravel'],
                'url' => 'https://github.com/example-labs/sample-package/pull/1',
            ],
            [
                'title' => 'Example Labs',
                'description' => 'Started an organization for small open-source utilities and shared configuration.',
                'organization' => 'Example Labs',
                'type' => 'Organization',
                'date' => 'January 20, 2025',
                'tags' => ['Projects', 'Developer Tools'],
                'url' => 'https://github.com/example-labs',
            ],
            [
                'title' => 'Documentation fixes',
                'description' => 'Small corrections and clearer examples across the documentation of tools I use.',
                'organization' => 'Various projects',
                'type' => 'Contributions',
                'date' => '2023 — 2026',
                'tags' => ['Contributions', 'Documentation'],
                'url' => 'https://github.com/example',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function experiences(): array
    {
        return [
            [
                'role' => 'Lead Software Engineer',
                'company' => 'Globex',
                'description' => 'The billing platform for a fictional B2B software company.',
                'period' => 'Feb 2022 — Present',
                'is_current' => true,
                'url' => 'https://example.com/globex',
                'highlights' => [
                    'Lead a team of five across billing, invoicing, and account management',
                    'Moved 40,000 accounts to a new invoicing system without downtime',
                    'Cut failed renewals by 18% with clearer dunning emails and retry schedules',
                ],
            ],
            [
                'role' => 'Software Engineer',
                'company' => 'Initech',
                'description' => 'Internal tools for scheduling and reporting across field teams.',
                'period' => 'Jun 2019 — Jan 2022',
                'is_current' => false,
                'url' => 'https://example.com/initech',
                'highlights' => [
                    'Built the scheduling engine used by 1,200 staff',
                    'Replaced nightly spreadsheet exports with live reports',
                ],
            ],
            [
                'role' => 'Junior Developer',
                'company' => 'Acme Studio',
                'description' => '',
                'period' => 'Sep 2017 — May 2019',
                'is_current' => false,
                'highlights' => [
                    'Delivered client websites and small Laravel applications',
                    'Introduced automated tests and code review to the team',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function education(): array
    {
        return [
            [
                'degree' => 'BSc in Computer Science',
                'school' => 'Example University - Lisbon, Portugal',
                'institution' => 'Example University',
                'period' => '2013 — 2017',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function languages(): array
    {
        return [
            ['name' => 'English', 'level' => 'Native'],
            ['name' => 'Portuguese', 'level' => 'Fluent'],
            ['name' => 'Spanish', 'level' => 'Conversational'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function caseStudies(): array
    {
        return [
            [
                'title' => 'Moving 40,000 accounts to a new invoicing system',
                'context' => 'Globex · Lead Software Engineer · 2022 — Present',
                'problem' => 'The old invoicing code could not handle mid-cycle plan changes, and every fix risked sending customers the wrong invoice.',
                'constraints' => [
                    'Customers could not see any downtime during renewal periods',
                    'Every invoice had to match the old system to the cent during the move',
                ],
                'design' => [
                    'Ran both systems side by side and compared their invoices every night',
                    'Moved accounts in small batches behind a flag that could be turned off per account',
                ],
                'tradeoffs' => [
                    'Running two systems doubled the work for three months',
                    'Small batches made the move slower, but each failure stayed small',
                ],
                'results' => [
                    'All 40,000 accounts moved without downtime',
                    'Billing support tickets fell by a third in the following quarter',
                ],
                'technologies' => ['Laravel', 'PostgreSQL', 'Redis', 'Queues'],
            ],
            [
                'title' => 'Scheduling that keeps working on a bad connection',
                'context' => 'Initech · Software Engineer · 2019 — 2022',
                'problem' => 'Field staff often lost signal, and unsaved reports were lost with it.',
                'constraints' => [
                    'Staff used older phones on slow networks',
                    'Managers needed reports the same day',
                ],
                'design' => [
                    'Saved each form on the device first, then synced it in the background',
                    'Made every sync safe to retry, so duplicates could not appear',
                ],
                'tradeoffs' => [
                    'Managers can see a report a few minutes after it is written',
                ],
                'results' => [
                    'Lost reports dropped to almost none',
                    'Same-day reporting rose from 60% to 95%',
                ],
                'technologies' => ['Laravel', 'Livewire', 'MySQL'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function testimonials(): array
    {
        return [
            [
                'quote' => 'Sam took the riskiest project we had and made it feel routine. We always knew where it stood and what would happen next.',
                'author' => 'Jordan Example',
                'role' => 'VP of Engineering',
                'company' => 'Globex',
            ],
            [
                'quote' => 'Sam writes code that is easy to review and explains decisions without making anyone feel slow for asking.',
                'author' => 'Riley Sample',
                'role' => 'Senior Engineer',
                'company' => 'Globex',
            ],
            [
                'quote' => 'The scheduling tool Sam built replaced three spreadsheets and a lot of phone calls. Our field teams still use it every day.',
                'author' => 'Casey Placeholder',
                'role' => 'Operations Manager',
                'company' => 'Initech',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profileHighlights(): array
    {
        return [
            ['label' => 'Experience', 'value' => '9 yrs', 'detail' => 'Shipping to production', 'short_detail' => 'In production'],
            ['label' => 'Domain', 'value' => 'Billing', 'detail' => 'Subscriptions · Invoicing', 'short_detail' => 'Subscriptions'],
            ['label' => 'Timezone', 'value' => 'UTC+0', 'detail' => 'Lisbon', 'short_detail' => 'Lisbon'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function portfolioSections(): array
    {
        return [
            ['key' => 'about', 'eyebrow' => 'About', 'title' => 'Who I Am', 'description' => 'How I work and what I\'m looking for next.'],
            ['key' => 'skills', 'eyebrow' => 'Skills', 'title' => 'My Toolbox', 'description' => 'Core tools I work with, plus technologies I\'m familiar with.'],
            [
                'key' => 'work',
                'eyebrow' => 'Work',
                'title' => 'What I\'ve Built',
                'description' => 'A selection of products and tools I\'ve built and maintained.',
                'labels' => ['flagship' => 'Flagship projects', 'caseStudiesLink' => 'Read the case studies', 'supporting' => 'Supporting projects', 'supportingDescription' => 'Side projects built alongside full-time work'],
            ],
            ['key' => 'case-studies', 'eyebrow' => 'Case Studies', 'title' => 'How I Approach Hard Problems', 'description' => 'The problem, the constraints, the design I chose, and what it achieved.'],
            ['key' => 'experience', 'eyebrow' => 'Experience', 'title' => 'Where I\'ve Worked', 'description' => 'Roles that shape how I build software.'],
            ['key' => 'education', 'eyebrow' => 'Education', 'title' => 'What I Studied', 'description' => 'The formal groundwork behind the hands-on experience.'],
            ['key' => 'testimonials', 'eyebrow' => 'Testimonials', 'title' => 'What Colleagues Say', 'description' => 'Feedback from people I have built with.'],
            ['key' => 'connect', 'eyebrow' => 'Connect', 'title' => 'Get in Touch', 'description' => 'For roles or project enquiries, email is the best way to reach me.'],
            ['key' => 'open-source', 'eyebrow' => 'Community work', 'title' => 'Open Source', 'description' => 'Contributions, projects, and tools I have built for other developers.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageContents(): array
    {
        return [
            ['key' => 'home', 'title' => 'Senior Full-Stack Engineer | SaaS & Developer Tools', 'description' => 'Sample portfolio of Sam Rivera, a fictional senior full-stack engineer.', 'image_alt' => 'Sam Rivera — Senior Full-Stack Engineer'],
            ['key' => 'resume', 'title' => 'Resume | Senior Full-Stack Engineer', 'description' => 'Sample resume of Sam Rivera, covering billing platforms, skills, and work history.', 'image_alt' => 'Sam Rivera — Senior Full-Stack Engineer'],
            ['key' => 'open-source', 'title' => 'Open Source | Laravel Projects & Contributions', 'description' => 'Sample open-source work by Sam Rivera, including Laravel packages and developer tools.', 'image_alt' => 'Sam Rivera — Senior Full-Stack Engineer'],
        ];
    }
}
