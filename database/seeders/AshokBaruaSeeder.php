<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MediaKey;
use App\Models\CaseStudy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Media;
use App\Models\OpenSourceContribution;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

final class AshokBaruaSeeder extends Seeder
{
    /**
     * Import Ashok Barua's portfolio content into an empty install. An existing
     * profile means the database already owns the content, so nothing changes.
     */
    public function run(): void
    {
        if (Profile::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
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

            foreach ($this->projects() as $projectOrder => $attributes) {
                /** @var array<int, array<string, mixed>> $links */
                $links = $attributes['links'] ?? [];
                unset($attributes['links']);

                $project = Project::query()->create([...$attributes, 'sort_order' => $projectOrder]);

                foreach ($links as $linkOrder => $link) {
                    $project->links()->create([...$link, 'sort_order' => $linkOrder]);
                }
            }
        });
    }

    /**
     * Copy the owner's files to storage. Names include a content hash because
     * nginx serves images as immutable, so a replaced file needs a new URL.
     */
    private function storeMedia(): void
    {
        $files = [
            'ashok-barua-portrait-640.webp' => MediaKey::Portrait640,
            'ashok-barua-portrait-960.webp' => MediaKey::Portrait960,
            'ashok-barua-avatar.webp' => MediaKey::Avatar,
            'ashok-barua.jpg' => MediaKey::Photo,
            'portfolio-og.png' => MediaKey::ShareImage,
            'favicon.ico' => MediaKey::FaviconIco,
            'favicon.png' => MediaKey::FaviconPng,
            'apple-touch-icon.png' => MediaKey::AppleTouchIcon,
        ];

        foreach ($files as $fileName => $key) {
            $source = database_path("seeders/owners/ashok-barua/{$fileName}");
            $hash = mb_substr((string) hash_file('sha256', $source), 0, 16);
            $path = "owner/{$key->value}-{$hash}." . File::extension($source);

            Storage::disk('public')->put($path, File::get($source));

            Media::query()->create([
                'key' => $key,
                'disk' => 'public',
                'path' => $path,
                'mime_type' => (string) File::mimeType($source),
                'size' => File::size($source),
                'original_name' => $fileName,
            ]);
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
            'headline' => 'Senior Full-Stack Engineer',
            'alternate_names' => ['Ashok Barua Akas', 'Akas'],
            'city' => 'Chattogram',
            'country' => 'Bangladesh',
            'timezone' => 'Asia/Dhaka',
            'timezone_label' => 'UTC+6',
            'phone_display' => '+880 1829 853914',
            'source_url' => 'https://github.com/ashokbaruaakas/ashokbaruaakas.com',
            'built_with' => 'Laravel · Inertia · Vue',
            'marquee_words' => ['schema', 'migrate', 'route', 'validate', 'authorize', 'transaction', 'encrypt', 'idempotent', 'webhook', 'dispatch', 'queue', 'retry', 'test', 'deploy', 'observe'],
            'knows_about_topics' => ['PHP', 'TypeScript', 'JavaScript', 'Python', 'SQL', 'Laravel', 'Vue.js', 'Nuxt.js', 'Inertia.js', 'Tailwind CSS', 'Livewire', 'Alpine.js', 'MySQL', 'PostgreSQL', 'Redis', 'Docker', 'CI/CD', 'HSM', 'Payment gateways', 'Business platforms'],
            'open_source_interests' => 'I enjoy working on Laravel, Inertia, Vue, developer tooling, AI workflows, and infrastructure projects.',
            'name' => 'Ashok Barua',
            'tagline' => 'Senior Full-Stack Engineer — Fintech & Business Platforms',
            'bio' => 'I build production-ready systems with **PHP, Laravel, TypeScript, and Vue**, from backend architecture and APIs to deployment and operations.',
            'location' => 'Chattogram, Bangladesh',
            'availability' => 'Open to Remote or Relocation',
            'about' => 'Senior Full-Stack Engineer with 7+ years building payment systems and business platforms in Laravel, PHP 8, Vue 3 and TypeScript. Led a team of six engineers on SiPay, a licensed e-money platform in Türkiye, where I built HSM-backed saved-card storage and drove the PHP 7.4 to 8.2 upgrade. Since 2023 I have been the senior engineer at Grow More Gaze, owning its platforms, servers and security end to end. I work agent-first and review every change that touches money, data or auth myself. I enjoy helping people grow. A classmate I mentored through problem solving, Git and best practices now leads a team himself.',
            'how_i_work' => 'I plan before I build, give every issue its own branch and environment, let my review agent check the code first, and then review it myself line by line. Anything touching money, data or auth gets the slowest read.',
            'side_projects' => 'Laravel and DevOps tooling I maintain on GitHub and under @thesoftpulze, plus merged contributions to Inertia.js and Raycast. See the full timeline of my open-source work.',
            'open_source_highlights' => [
                'Maintain LaraVibe Standards, LaraVibe-Vue, clawkit, Notify Fanout and Proton Pass Secret Fetcher',
                'Merged contributions to Inertia.js and Raycast',
            ],
            'github_username' => 'ashokbaruaakas',
            'organization' => 'thesoftpulze',
            'email' => 'ashokbaruaakas@gmail.com',
            'phone' => '+8801829853914',
            'familiar_skills' => [
                'React (with Inertia)',
                'Next.js',
                'Go',
                'Rust',
            ],
            'professional_summary' => 'Senior Full-Stack Engineer with 7+ years building payment systems and business platforms in Laravel, PHP 8, Vue 3 and TypeScript. Led a team of six engineers on SiPay, a licensed e-money platform in Türkiye, where I built HSM-backed saved-card storage and drove the PHP 7.4 to 8.2 upgrade. Since 2023 I have been the senior engineer at Grow More Gaze, owning its platforms, servers and security end to end. I work agent-first and review every change that touches money, data or auth myself.',
            'website_url' => 'https://ashokbaruaakas.com',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function socialLinks(): array
    {
        return [
            [
                'platform' => 'LinkedIn',
                'in_structured_data' => true,
                'url' => 'https://www.linkedin.com/in/ashokbaruaakas',
                'icon' => 'linkedin',
                'is_primary' => true,
            ],
            [
                'platform' => 'GitHub',
                'in_structured_data' => true,
                'url' => 'https://github.com/ashokbaruaakas',
                'icon' => 'github',
                'is_primary' => true,
            ],
            [
                'platform' => 'X',
                'in_structured_data' => true,
                'url' => 'https://x.com/ashokbaruaakas',
                'icon' => 'twitter',
                'is_primary' => false,
            ],
            [
                'platform' => 'Telegram',
                'in_structured_data' => true,
                'url' => 'https://t.me/ashokbaruaakas',
                'icon' => 'send',
                'is_primary' => false,
            ],
            [
                'platform' => 'WhatsApp',
                'url' => 'https://wa.me/8801829853914',
                'icon' => 'message-circle',
                'is_primary' => false,
            ],
            [
                'platform' => 'Discord',
                'url' => 'https://discord.com/users/611991650868133894',
                'icon' => 'message-square',
                'is_primary' => false,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function skillCategories(): array
    {
        return [
            [
                'name' => 'Languages',
                'items' => [
                    'PHP',
                    'TypeScript',
                    'JavaScript',
                    'Python',
                    'SQL',
                    'HTML',
                    'CSS',
                ],
            ],
            [
                'name' => 'Frameworks',
                'items' => [
                    'Laravel',
                    'Vue.js',
                    'Nuxt.js',
                    'Inertia.js',
                    'Tailwind CSS',
                    'Livewire',
                    'Alpine.js',
                ],
            ],
            [
                'name' => 'Backend & Architecture',
                'items' => [
                    'REST API design',
                    'System design',
                    'Queues & Horizon',
                    'Webhooks',
                    'Idempotency',
                    'PCI-DSS-aligned systems',
                    'Observability',
                    'Legacy upgrades',
                ],
            ],
            [
                'name' => 'Databases',
                'items' => [
                    'MySQL',
                    'PostgreSQL',
                    'Redis',
                ],
            ],
            [
                'name' => 'Practices',
                'items' => [
                    'Testing (PHPUnit, Pest)',
                    'Code review',
                    'Mentoring and team leadership',
                    'Hiring interviews',
                    'Agile',
                ],
            ],
            [
                'name' => 'DevOps & Tools',
                'items' => [
                    'Linux',
                    'Nginx',
                    'Docker',
                    'Git',
                    'GitHub Actions',
                    'CI/CD',
                    'Elasticsearch',
                    'Graylog',
                    'MQTT',
                    'Deployer',
                ],
            ],
            [
                'name' => 'AI & Agent Engineering',
                'items' => [
                    'Claude Code',
                    'OpenCode',
                    'OpenClaw (OpenRouter)',
                    'MCP',
                    'LLM APIs',
                    'Agent review workflows',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'name' => 'SiPay',
                'description' => 'A licensed e-money and payment platform in Türkiye, built and supported by Softrobotics — mobile banking, merchant banking, payment gateways, and HSM-backed saved cards.',
                'technologies' => [
                    'Laravel',
                    'Fintech',
                    'Microservices',
                    'Elasticsearch',
                    'Graylog',
                    'HSM',
                ],
                'stars' => 0,
                'language' => 'PHP',
                'demo_url' => 'https://sipay.com.tr/en/',
                'tier' => 1,
                'period' => '2021 — 2023',
                'role' => 'Core payments engineer, promoted to team lead for the final four months.',
                'metric' => 'Team lead (final 4 mo)',
                'is_public_repo' => false,
                'link_label' => 'Product',
                'show_on_resume' => true,
            ],
            [
                'name' => 'Grow More Gaze',
                'description' => 'The operations platform powering an international link-building agency — service ordering, invoicing, multi-gateway payments, email campaigns, HRM, and payroll.',
                'technologies' => [
                    'Laravel',
                    'Vue.js',
                    'TypeScript',
                    'MySQL',
                    'CI/CD',
                ],
                'stars' => 0,
                'language' => 'PHP',
                'demo_url' => 'https://growmoregaze.com',
                'tier' => 1,
                'period' => '2023 — Present',
                'role' => 'Senior engineer — own all of its technology, from architecture and code to servers, security and deployments.',
                'metric' => 'Senior engineer',
                'is_public_repo' => false,
                'link_label' => 'Product',
                'show_on_resume' => true,
            ],
            [
                'name' => 'Stellar BD — HRM & Payroll System',
                'description' => 'An HRM system handling attendance, full payroll, and shift management for a large, multi-site workforce.',
                'technologies' => [
                    'Python',
                    'Web2py',
                    'MySQL',
                    'MQTT',
                    'IoT',
                ],
                'stars' => 0,
                'language' => 'Python',
                'demo_url' => 'https://stellarbd.com',
                'tier' => 1,
                'period' => '2019 — 2021',
                'role' => 'Built the core HRM, payroll, and shift modules and integrated fingerprint hardware via MQTT.',
                'is_public_repo' => false,
                'link_label' => 'Company',
                'show_on_resume' => true,
            ],
            [
                'name' => 'bizztechsz.com',
                'description' => 'A platform for worldwide bulk-order quotes and company portfolios. Freelance client, still live and maintained by me.',
                'technologies' => [
                    'Laravel',
                    'Vue.js',
                    'TypeScript',
                    'Tailwind CSS',
                    'MySQL',
                ],
                'stars' => 0,
                'language' => 'PHP',
                'demo_url' => 'https://bizztechsz.com',
                'tier' => 2,
                'is_public_repo' => false,
                'show_on_resume' => false,
            ],
            [
                'name' => 'clawkit',
                'description' => 'A Docker and AI tooling wrapper for OpenClaw, published to GHCR through automated CI/CD.',
                'owner' => 'ashokbaruaakas',
                'repo' => 'clawkit',
                'technologies' => [
                    'Docker',
                    'GitHub Actions',
                    'CI/CD',
                    'OpenClaw',
                ],
                'stars' => 1,
                'language' => 'Docker',
                'demo_url' => 'https://github.com/ashokbaruaakas/clawkit',
                'tier' => 2,
                'metric' => '700+ image pulls',
                'is_public_repo' => true,
                'show_on_resume' => true,
                'links' => [
                    [
                        'label' => 'GHCR',
                        'url' => 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                        'is_primary' => false,
                    ],
                ],
            ],
            [
                'name' => 'LaraVibe',
                'description' => 'Open-source Laravel standards I maintain: LaraVibe Standards (generators for actions, DTOs and enums, base API resources, and guidelines and skills for AI agents via Laravel Boost) and LaraVibe-Vue, a Laravel starter kit on Vue 3 and Inertia.js.',
                'technologies' => [
                    'Laravel',
                    'PHP',
                    'Vue.js',
                    'Inertia.js',
                    'TypeScript',
                    'AI agents',
                ],
                'stars' => 0,
                'language' => 'PHP',
                'tier' => 2,
                'is_public_repo' => true,
                'show_on_resume' => true,
                'links' => [
                    [
                        'label' => 'LaraVibe Standards',
                        'url' => 'https://github.com/thesoftpulze/laravibe-standards',
                        'is_primary' => true,
                    ],
                    [
                        'label' => 'LaraVibe-Vue',
                        'url' => 'https://github.com/thesoftpulze/laravibe-vue',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'name' => 'GitHub Actions for CI/CD',
                'description' => 'Two GitHub Marketplace actions I maintain: Notify Fanout, which sends CI/CD notifications to one or many chats from a single step, and Proton Pass Secret Fetcher, which loads secrets from Proton Pass into a workflow with masking and no saved login on the runner.',
                'technologies' => [
                    'GitHub Actions',
                    'CI/CD',
                    'TypeScript',
                    'Bash',
                    'Security',
                ],
                'stars' => 0,
                'language' => 'TypeScript',
                'tier' => 2,
                'is_public_repo' => true,
                'show_on_resume' => true,
                'links' => [
                    [
                        'label' => 'Notify Fanout',
                        'url' => 'https://github.com/ashokbaruaakas/notify-fanout-action',
                        'is_primary' => true,
                    ],
                    [
                        'label' => 'Notify Fanout Marketplace',
                        'url' => 'https://github.com/marketplace/actions/notify-fanout',
                        'is_primary' => false,
                    ],
                    [
                        'label' => 'Proton Pass Secret Fetcher',
                        'url' => 'https://github.com/ashokbaruaakas/proton-pass-action',
                        'is_primary' => true,
                    ],
                    [
                        'label' => 'Proton Pass Marketplace',
                        'url' => 'https://github.com/marketplace/actions/proton-pass-secret-fetcher',
                        'is_primary' => false,
                    ],
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
                'title' => 'Notify Fanout',
                'description' => 'Built and published a GitHub Action that fans out CI/CD notifications to multiple chats from one step, with event presets, templates, per-target settings, and retries. v1 ships with Telegram.',
                'organization' => 'Ashok Barua',
                'type' => 'Project',
                'date' => 'October 17, 2026',
                'tags' => [
                    'Projects',
                    'CI/CD',
                    'Developer Tools',
                ],
                'url' => 'https://github.com/ashokbaruaakas/notify-fanout-action',
                'secondary_url' => 'https://github.com/marketplace/actions/notify-fanout',
            ],
            [
                'title' => 'LaraVibe Standards',
                'description' => 'Created an open-source package for consistent Laravel conventions, DTOs, enums, resources, and developer tooling.',
                'organization' => 'The SoftPulze',
                'type' => 'Project',
                'date' => 'July 23, 2026',
                'tags' => [
                    'Projects',
                    'Laravel',
                    'Developer Tools',
                ],
                'url' => 'https://github.com/thesoftpulze/laravibe-standards',
                'secondary_url' => 'https://packagist.org/packages/softpulze/laravibe-standards',
            ],
            [
                'title' => 'clawkit',
                'description' => 'Built an OpenClaw wrapper image with Linuxbrew and development tooling, published to GHCR with automated releases.',
                'organization' => 'Ashok Barua',
                'type' => 'Project',
                'date' => 'May 15, 2026',
                'tags' => [
                    'Projects',
                    'Infrastructure',
                    'AI',
                ],
                'url' => 'https://github.com/ashokbaruaakas/clawkit',
                'secondary_url' => 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                'metric' => '700+ image pulls',
            ],
            [
                'title' => 'LaraVibe-Vue',
                'description' => 'Built a modern Laravel 13 starter kit with Vue 3, Inertia.js v3, SSR, authentication, and type-safe route helpers.',
                'organization' => 'The SoftPulze',
                'type' => 'Project',
                'date' => 'August 22, 2025',
                'tags' => [
                    'Projects',
                    'Laravel',
                    'Vue',
                ],
                'url' => 'https://github.com/thesoftpulze/laravibe-vue',
            ],
            [
                'title' => 'Inertia Laravel: inertiaProps',
                'description' => 'Introduced the inertiaProps testing helper with nested dot-notation support for clearer Inertia response assertions.',
                'organization' => 'Inertia.js',
                'type' => 'Pull Request',
                'date' => 'June 5, 2025',
                'tags' => [
                    'Pull Requests',
                    'Laravel',
                    'Developer Tools',
                ],
                'url' => 'https://github.com/inertiajs/inertia-laravel/pull/700',
            ],
            [
                'title' => 'The SoftPulze',
                'description' => 'Started The SoftPulze as an organization for open-source and private software projects.',
                'organization' => 'The SoftPulze',
                'type' => 'Organization',
                'date' => 'January 17, 2025',
                'tags' => [
                    'Projects',
                    'Developer Tools',
                ],
                'url' => 'https://github.com/thesoftpulze',
            ],
            [
                'title' => 'DevPulse CLI',
                'description' => 'Started an open-source PHP CLI for standardizing development scripts and server workflows.',
                'organization' => 'The SoftPulze',
                'type' => 'Project',
                'date' => 'December 1, 2024',
                'tags' => [
                    'Projects',
                    'Developer Tools',
                    'Infrastructure',
                ],
                'url' => 'https://github.com/thesoftpulze/devpulse-cli',
            ],
            [
                'title' => 'Minor OSS contributions',
                'description' => 'Small contributions to Raycast Extensions, including Paste in Active App for Raycast Ollama and a Brew cleanup command.',
                'organization' => 'Raycast Extensions',
                'type' => 'Contributions',
                'date' => '2024 — 2026',
                'tags' => [
                    'Contributions',
                    'Raycast',
                    'Developer Tools',
                ],
                'url' => 'https://github.com/raycast/extensions',
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
                'role' => 'Senior Full-Stack Engineer',
                'company' => 'Grow More Gaze',
                'description' => 'The platforms that run an international link-building agency: ordering, invoicing, payments, email campaigns and HR/payroll.',
                'period' => 'Oct 2023 — Present',
                'is_current' => true,
                'url' => 'https://growmoregaze.com',
                'highlights' => [
                    'Joined to fill the company\'s senior engineering role; own all of its technology, from architecture and code to servers, security and deployments',
                    'Built and run the agency\'s ordering portal, invoicing, payments, email campaigns and HR/payroll, plus several internal projects',
                    'Integrated 7 payment gateways with idempotent webhook handling, so retries and duplicate callbacks never double-charge',
                    'Run CI/CD with zero-downtime deploys, server hardening and monitoring',
                    'Agent-first workflow: plan first on complex work, one isolated branch and environment per issue, automated review by my own review-agent skill, then line-by-line human review, with extra care on payment and auth code',
                ],
            ],
            [
                'role' => 'Independent Contractor and Open Source Maintainer',
                'company' => 'Self-employed',
                'description' => '',
                'period' => 'May 2026 — Present (alongside Grow More Gaze)',
                'is_current' => true,
                'highlights' => [
                    'Contracted directly by the client to build bizztechsz.com, a platform for worldwide bulk-order quotes and company portfolios; built to the client\'s requirements and still live, and I continue to maintain and extend it',
                    'Maintain open-source Laravel and DevOps tooling (LaraVibe Standards, LaraVibe-Vue, clawkit with 700+ image pulls, Notify Fanout, Proton Pass Secret Fetcher) and contribute upstream to inertia-laravel and Raycast',
                ],
            ],
            [
                'role' => 'Senior Software Engineer, Team Lead of 6 (last 4 months)',
                'company' => 'Softrobotics Bangladesh Ltd',
                'description' => 'Core payments engineer on SiPay, a licensed e-money and payment platform in Türkiye built by Softrobotics.',
                'period' => 'Sep 2021 — Sep 2023',
                'url' => 'https://sipay.com.tr/en/',
                'highlights' => [
                    'Led a team of six engineers: sprint planning, code reviews, hiring interviews, and balancing workload so the team kept delivering and stayed happy',
                    'Brought an engineer who had asked to leave another team into mine; through one-to-one support he became a fully engaged contributor, and management recognised me for it',
                    'Developed merchant banking, mobile banking, and payment modules for a high-volume production payment platform',
                    'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                    'Built and maintained microservices instrumented with Elasticsearch and Graylog, making production incidents traceable across services',
                    'Built the platform side of the HSM integration for PCI-DSS-aligned saved-card storage, encryption, and card management',
                    'Drove core parts of the legacy upgrade from PHP 7.4 and an older Laravel release to PHP 8.2 and a modern Laravel version',
                ],
            ],
            [
                'role' => 'Full-Stack Web Developer & Designer',
                'company' => 'Stellar BD Ltd',
                'description' => 'Built HRM/payroll systems wired to IoT attendance hardware, plus client web apps.',
                'period' => 'Oct 2019 — Aug 2021',
                'url' => 'https://stellarbd.com',
                'highlights' => [
                    'Built core HRM, payroll, and shift modules integrated with fingerprint scanners for a large, multi-site workforce',
                    'Implemented real-time attendance sync via MQTT across the company\'s attendance devices, replacing nightly manual imports',
                    'Maintained and extended the HRM on the legacy Web2py (Python) framework',
                    'Built client websites and automation scripts in PHP, JavaScript, and Python',
                    'Designed responsive UIs and managed hosting end-to-end',
                ],
            ],
            [
                'role' => 'Full-Stack Web Developer',
                'company' => 'Multiplex Web Design',
                'description' => 'Delivered custom web apps for SMB clients, end-to-end.',
                'period' => 'Mar 2018 — Sep 2019',
                'highlights' => [
                    'Owned full project lifecycles for SMB clients, from requirements through deployment',
                    'Delivered full-stack features with PHP, JavaScript, MySQL, and responsive design',
                    'Worked directly with clients on scoping, timelines, and delivery',
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
                'degree' => 'BSc in Computer Science (evening program, while working full-time)',
                'school' => 'East Delta University - Chattogram, Bangladesh',
                'institution' => 'East Delta University',
                'period' => '2018 — 2021',
            ],
            [
                'degree' => 'Diploma in Computer Science',
                'school' => 'Bangladesh Sweden Polytechnic Institute - Kaptai, Rangamati, Bangladesh',
                'institution' => 'Bangladesh Sweden Polytechnic Institute',
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
            [
                'name' => 'Bengali',
                'level' => 'Native',
            ],
            [
                'name' => 'English',
                'level' => 'Fluent',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function caseStudies(): array
    {
        return [
            [
                'title' => 'Duplicate-safe payment collection across 7 gateways',
                'context' => 'Grow More Gaze · Senior Full-Stack Engineer · 2023 — Present',
                'problem' => 'The agency collects payments from clients worldwide through 7 gateways with very different webhook behaviors. Retried or duplicated webhooks risked double-counting payments or leaving invoices in the wrong state, and every new gateway threatened a checkout rewrite.',
                'constraints' => [
                    'Solo-built and operated, so the pipeline had to stay simple to debug and extend',
                    'Gateways differ in webhook retry policies, signatures, and settlement flows',
                    'Every invoice must reflect exactly one settled payment',
                ],
                'design' => [
                    'A normalized payment pipeline: each gateway sits behind an adapter that feeds one internal payment state machine',
                    'Idempotency keys and webhook deduplication make retried or double-delivered webhooks no-ops',
                    'Signature verification and amount/currency cross-checks run before any invoice is marked paid',
                    'Queue-based webhook processing, so a slow or unreachable gateway never blocks checkout',
                ],
                'tradeoffs' => [
                    'A gateway-agnostic core means gateway-specific features live in adapters instead of the main flow',
                    'Async webhook settlement finalizes invoices moments after checkout rather than inline with the redirect',
                ],
                'results' => [
                    'Duplicate webhook deliveries are absorbed without duplicate charges or corrupted invoice states',
                    'Adding a gateway became an adapter instead of a rewrite — 7 live today, including bKash and Nagad',
                    'Payment issues are traceable end-to-end through one pipeline instead of 7 separate integrations',
                ],
                'technologies' => [
                    'Laravel',
                    'MySQL',
                    'Redis',
                    'Horizon',
                    'Webhooks',
                ],
            ],
            [
                'title' => 'HSM-backed saved cards for a payment platform',
                'context' => 'SiPay · Core payments engineer · 2021 — 2023',
                'problem' => 'Merchants needed saved cards for repeat payments, but card data could never be stored or handled in plaintext. The HSM provided the cryptography; SiPay needed a dependable way to use it on the live payment path.',
                'constraints' => [
                    'Card numbers must never be readable in application storage or logs',
                    'Saving and charging cards sits on the live payment path, so the integration had to be fast and reliable',
                    'The HSM is a vendor device with its own protocol and only basic documentation',
                ],
                'design' => [
                    'Built the SiPay-side integration that communicates with the HSM to encrypt, decrypt, and manage card data',
                    'Merchants and other services work with card references instead of raw card numbers',
                    'Handled the saved-card lifecycle end to end: save, list, charge, and delete',
                    'Card data kept out of the logs shipped to Graylog',
                ],
                'tradeoffs' => [
                    'Relied on proven vendor hardware for cryptography instead of building it in software, accepting the device\'s protocol and limits',
                    'Every save and charge depends on the HSM, so its availability became part of the payment path',
                ],
                'results' => [
                    'Merchants got saved-card payments without card data ever being stored in plaintext',
                    'Card security handled by dedicated hardware, supporting SiPay\'s PCI-DSS-aligned approach',
                ],
                'technologies' => [
                    'Laravel',
                    'HSM',
                    'Microservices',
                    'Graylog',
                ],
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
                'quote' => 'Ashok built the platform our whole agency runs on — ordering, invoicing, payments, and payroll — and he runs it on his own. He thinks about failure modes before they happen, and handles everything from architecture to servers.',
                'author' => 'Md. Shehad',
                'role' => 'Founder & CEO',
                'company' => 'Grow More Gaze',
            ],
            [
                'quote' => 'On SiPay, Ashok owned critical payment work end to end, from saved-card security to everyday payment flows. He held a high bar for code quality, and when we needed a team lead, he stepped up and led the team through delivery.',
                'author' => 'Rajib Deb',
                'role' => 'CEO',
                'company' => 'Softrobotics Bangladesh Ltd',
            ],
            [
                'quote' => 'Ashok and I worked side by side on SiPay\'s payment modules. He takes ownership of hard problems, writes code that\'s easy to review, and played a big part in moving our codebase to PHP 8.2.',
                'author' => 'Md. Amanot Ali',
                'role' => 'Senior Software Engineer & Team Lead',
                'company' => 'Softrobotics Bangladesh Ltd',
            ],
            [
                'quote' => 'When a payment flow broke in production, Ashok was one of the first people we called. He digs into the logs, finds the real cause, and explains the fix clearly — and he helps the engineers around him grow.',
                'author' => 'Md. Arafat Uddin',
                'role' => 'Senior Software Engineer & Team Lead',
                'company' => 'Softrobotics Bangladesh Ltd',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profileHighlights(): array
    {
        return [
            [
                'label' => 'Experience',
                'value' => '7+ yrs',
                'detail' => 'Shipping to production',
                'short_detail' => 'In production',
            ],
            [
                'label' => 'Domain',
                'value' => 'Payments',
                'detail' => 'SiPay · HSM · PCI-DSS',
                'short_detail' => 'SiPay · HSM',
            ],
            [
                'label' => 'Timezone',
                'value' => 'UTC+6',
                'detail' => 'Chattogram',
                'short_detail' => 'Chattogram',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function portfolioSections(): array
    {
        return [
            [
                'key' => 'about',
                'eyebrow' => 'About',
                'title' => 'Who I Am',
                'description' => 'How I work and what I\'m looking for next.',
            ],
            [
                'key' => 'skills',
                'eyebrow' => 'Skills',
                'title' => 'My Toolbox',
                'description' => 'Core tools and technologies I work with, plus technologies I’m familiar with.',
            ],
            [
                'key' => 'work',
                'eyebrow' => 'Work',
                'title' => 'What I\'ve Built',
                'description' => 'A selection of production systems, platforms, and tools I\'ve built and maintained.',
                'labels' => ['flagship' => 'Flagship projects', 'caseStudiesLink' => 'Read the SiPay and Grow More Gaze case studies', 'supporting' => 'Supporting projects', 'supportingDescription' => 'Side projects built alongside full-time work'],
            ],
            [
                'key' => 'case-studies',
                'eyebrow' => 'Case Studies',
                'title' => 'How I Approach Hard Problems',
                'description' => 'The problem, the constraints, the design I chose, and what it achieved.',
            ],
            [
                'key' => 'experience',
                'eyebrow' => 'Experience',
                'title' => 'Where I\'ve Worked',
                'description' => 'Roles and contributions that shape how I build software.',
            ],
            [
                'key' => 'education',
                'eyebrow' => 'Education',
                'title' => 'What I Studied',
                'description' => 'The formal groundwork behind the hands-on experience.',
            ],
            [
                'key' => 'testimonials',
                'eyebrow' => 'Testimonials',
                'title' => 'What Colleagues Say',
                'description' => 'Feedback from people I have built with.',
            ],
            [
                'key' => 'connect',
                'eyebrow' => 'Connect',
                'title' => 'Get in Touch',
                'description' => 'For roles, consulting, or project enquiries, email or LinkedIn is the best way to reach me. I usually reply within one business day.',
            ],
            [
                'key' => 'open-source',
                'eyebrow' => 'Community work',
                'title' => 'Open Source',
                'description' => 'Contributions, projects, and tools I have built for the developer community.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageContents(): array
    {
        return [
            [
                'key' => 'home',
                'title' => 'Senior Full-Stack Engineer | Fintech & Business Platforms',
                'description' => 'Senior full-stack engineer focused on fintech payments and business platforms, with SiPay, HSM, card-saving, and end-to-end business platform experience.',
                'image_alt' => 'Ashok Barua — Senior Full-Stack Engineer',
            ],
            [
                'key' => 'resume',
                'title' => 'Resume | Senior Full-Stack Engineer',
                'description' => 'Review Ashok Barua’s resume covering fintech payments, SiPay, HSM and card saving, business platform ownership, technical skills, and work history.',
                'image_alt' => 'Ashok Barua — Senior Full-Stack Engineer',
            ],
            [
                'key' => 'open-source',
                'title' => 'Open Source | Laravel Projects & Contributions',
                'description' => 'Explore Ashok Barua’s open-source contributions, Laravel projects, developer tools, and community work across GitHub, Packagist, and developer tooling.',
                'image_alt' => 'Ashok Barua — Senior Full-Stack Engineer',
            ],
        ];
    }
}
