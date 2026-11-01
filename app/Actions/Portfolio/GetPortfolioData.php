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

class GetPortfolioData
{
    /**
     * Build the portfolio data used by public pages.
     */
    public function handle(): PortfolioDTO
    {
        return new PortfolioDTO(
            name: 'Ashok Barua',
            tagline: 'Senior Full-Stack Engineer — Fintech & Business Platforms',
            bio: 'I build production-ready systems with **PHP, Laravel, TypeScript, and Vue**, from backend architecture and APIs to deployment and operations.',
            location: 'Chattogram, Bangladesh',
            availability: 'Open to Remote or Relocation',
            about: 'Senior Full-Stack Engineer with 7+ years building payment systems and business platforms in Laravel, PHP 8, Vue 3 and TypeScript. Led a team of six engineers on SiPay, a licensed e-money platform in Türkiye, where I built HSM-backed saved-card storage and drove the PHP 7.4 to 8.2 upgrade. Since 2023 I have been the senior engineer at Grow More Gaze, owning its platforms, servers and security end to end. I work agent-first and review every change that touches money, data or auth myself. I enjoy helping people grow. A classmate I mentored through problem solving, Git and best practices now leads a team himself.',
            howIWork: 'I plan before I build, give every issue its own branch and environment, let my review agent check the code first, and then review it myself line by line. Anything touching money, data or auth gets the slowest read.',
            sideProjects: 'Laravel and DevOps tooling I maintain on GitHub and under @thesoftpulze, plus merged contributions to Inertia.js and Raycast. See the full timeline of my open-source work.',
            openSourceHighlights: [
                'Maintain LaraVibe Standards, LaraVibe-Vue, clawkit, Notify Fanout and Proton Pass Secret Fetcher',
                'Merged contributions to Inertia.js and Raycast',
            ],
            githubUsername: 'ashokbaruaakas',
            organization: 'thesoftpulze',
            email: 'ashokbaruaakas@gmail.com',
            phone: '+8801829853914',
            socialLinks: [
                new SocialLinkDTO(platform: 'LinkedIn', url: 'https://www.linkedin.com/in/ashokbaruaakas', icon: 'linkedin', isPrimary: true),
                new SocialLinkDTO(platform: 'GitHub', url: 'https://github.com/ashokbaruaakas', icon: 'github', isPrimary: true),
                new SocialLinkDTO(platform: 'X', url: 'https://x.com/ashokbaruaakas', icon: 'twitter'),
                new SocialLinkDTO(platform: 'Telegram', url: 'https://t.me/ashokbaruaakas', icon: 'send'),
                new SocialLinkDTO(platform: 'WhatsApp', url: 'https://wa.me/8801829853914', icon: 'message-circle'),
                new SocialLinkDTO(platform: 'Discord', url: 'https://discord.com/users/611991650868133894', icon: 'message-square'),
            ],
            skills: [
                new SkillCategoryDTO(category: 'Languages', items: ['PHP', 'TypeScript', 'JavaScript', 'Python', 'SQL', 'HTML', 'CSS']),
                new SkillCategoryDTO(category: 'Frameworks', items: ['Laravel', 'Vue.js', 'Nuxt.js', 'Inertia.js', 'Tailwind CSS', 'Livewire', 'Alpine.js']),
                new SkillCategoryDTO(category: 'Backend & Architecture', items: ['REST API design', 'System design', 'Queues & Horizon', 'Webhooks', 'Idempotency', 'PCI-DSS-aligned systems', 'Observability', 'Legacy upgrades']),
                new SkillCategoryDTO(category: 'Databases', items: ['MySQL', 'PostgreSQL', 'Redis']),
                new SkillCategoryDTO(category: 'Practices', items: ['Testing (PHPUnit, Pest)', 'Code review', 'Mentoring and team leadership', 'Hiring interviews', 'Agile']),
                new SkillCategoryDTO(category: 'DevOps & Tools', items: ['Linux', 'Nginx', 'Docker', 'Git', 'GitHub Actions', 'CI/CD', 'Elasticsearch', 'Graylog', 'MQTT', 'Deployer']),
                new SkillCategoryDTO(category: 'AI & Agent Engineering', items: ['Claude Code', 'OpenCode', 'OpenClaw (OpenRouter)', 'MCP', 'LLM APIs', 'Agent review workflows']),
            ],
            projects: [
                new ProjectDTO(
                    name: 'SiPay',
                    description: 'A licensed e-money and payment platform in Türkiye, built and supported by Softrobotics — mobile banking, merchant banking, payment gateways, and HSM-backed saved cards.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Fintech', 'Microservices', 'Elasticsearch', 'Graylog', 'HSM'],
                    stars: 0,
                    language: 'PHP',
                    tier: 1,
                    period: '2021 — 2023',
                    role: 'Core payments engineer, promoted to team lead for the final four months.',
                    metric: 'Team lead (final 4 mo)',
                    demoUrl: 'https://sipay.com.tr/en/',
                    linkLabel: 'Product',
                ),
                new ProjectDTO(
                    name: 'Grow More Gaze',
                    description: 'The operations platform powering an international link-building agency — service ordering, invoicing, multi-gateway payments, email campaigns, HRM, and payroll.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'MySQL', 'CI/CD'],
                    stars: 0,
                    language: 'PHP',
                    tier: 1,
                    period: '2023 — Present',
                    role: 'Senior engineer — own all of its technology, from architecture and code to servers, security and deployments.',
                    metric: 'Senior engineer',
                    demoUrl: 'https://growmoregaze.com',
                    linkLabel: 'Product',
                ),
                new ProjectDTO(
                    name: 'Stellar BD — HRM & Payroll System',
                    description: 'An HRM system handling attendance, full payroll, and shift management for a large, multi-site workforce.',
                    owner: null,
                    repo: null,
                    technologies: ['Python', 'Web2py', 'MySQL', 'MQTT', 'IoT'],
                    stars: 0,
                    language: 'Python',
                    tier: 1,
                    period: '2019 — 2021',
                    role: 'Built the core HRM, payroll, and shift modules and integrated fingerprint hardware via MQTT.',
                    demoUrl: 'https://stellarbd.com',
                    linkLabel: 'Company',
                ),
                new ProjectDTO(
                    name: 'bizztechsz.com',
                    description: 'A platform for worldwide bulk-order quotes and company portfolios. Freelance client, still live and maintained by me.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'Tailwind CSS', 'MySQL'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://bizztechsz.com',
                    isPublicRepo: false,
                    showOnResume: false,
                ),
                new ProjectDTO(
                    name: 'clawkit',
                    description: 'A Docker and AI tooling wrapper for OpenClaw, published to GHCR through automated CI/CD.',
                    owner: 'ashokbaruaakas',
                    repo: 'clawkit',
                    technologies: ['Docker', 'GitHub Actions', 'CI/CD', 'OpenClaw'],
                    stars: 1,
                    language: 'Docker',
                    demoUrl: 'https://github.com/ashokbaruaakas/clawkit',
                    metric: '700+ image pulls',
                    isPublicRepo: true,
                    links: [
                        new ProjectLinkDTO(label: 'GHCR', url: 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit'),
                    ],
                ),
                new ProjectDTO(
                    name: 'LaraVibe',
                    description: 'Open-source Laravel standards I maintain: LaraVibe Standards (generators for actions, DTOs and enums, base API resources, and guidelines and skills for AI agents via Laravel Boost) and LaraVibe-Vue, a Laravel starter kit on Vue 3 and Inertia.js.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'PHP', 'Vue.js', 'Inertia.js', 'TypeScript', 'AI agents'],
                    stars: 0,
                    language: 'PHP',
                    isPublicRepo: true,
                    links: [
                        new ProjectLinkDTO(label: 'LaraVibe Standards', url: 'https://github.com/thesoftpulze/laravibe-standards', isPrimary: true),
                        new ProjectLinkDTO(label: 'LaraVibe-Vue', url: 'https://github.com/thesoftpulze/laravibe-vue', isPrimary: true),
                    ],
                ),
                new ProjectDTO(
                    name: 'GitHub Actions for CI/CD',
                    description: 'Two GitHub Marketplace actions I maintain: Notify Fanout, which sends CI/CD notifications to one or many chats from a single step, and Proton Pass Secret Fetcher, which loads secrets from Proton Pass into a workflow with masking and no saved login on the runner.',
                    owner: null,
                    repo: null,
                    technologies: ['GitHub Actions', 'CI/CD', 'TypeScript', 'Bash', 'Security'],
                    stars: 0,
                    language: 'TypeScript',
                    isPublicRepo: true,
                    links: [
                        new ProjectLinkDTO(label: 'Notify Fanout', url: 'https://github.com/ashokbaruaakas/notify-fanout-action', isPrimary: true),
                        new ProjectLinkDTO(label: 'Notify Fanout Marketplace', url: 'https://github.com/marketplace/actions/notify-fanout'),
                        new ProjectLinkDTO(label: 'Proton Pass Secret Fetcher', url: 'https://github.com/ashokbaruaakas/proton-pass-action', isPrimary: true),
                        new ProjectLinkDTO(label: 'Proton Pass Marketplace', url: 'https://github.com/marketplace/actions/proton-pass-secret-fetcher'),
                    ],
                ),
            ],
            openSourceContributions: [
                new OpenSourceContributionDTO(
                    title: 'Notify Fanout',
                    description: 'Built and published a GitHub Action that fans out CI/CD notifications to multiple chats from one step, with event presets, templates, per-target settings, and retries. v1 ships with Telegram.',
                    organization: 'Ashok Barua',
                    type: 'Project',
                    date: 'October 17, 2026',
                    tags: ['Projects', 'CI/CD', 'Developer Tools'],
                    url: 'https://github.com/ashokbaruaakas/notify-fanout-action',
                    secondaryUrl: 'https://github.com/marketplace/actions/notify-fanout',
                ),
                new OpenSourceContributionDTO(
                    title: 'LaraVibe Standards',
                    description: 'Created an open-source package for consistent Laravel conventions, DTOs, enums, resources, and developer tooling.',
                    organization: 'The SoftPulze',
                    type: 'Project',
                    date: 'July 23, 2026',
                    tags: ['Projects', 'Laravel', 'Developer Tools'],
                    url: 'https://github.com/thesoftpulze/laravibe-standards',
                    secondaryUrl: 'https://packagist.org/packages/softpulze/laravibe-standards',
                ),
                new OpenSourceContributionDTO(
                    title: 'clawkit',
                    description: 'Built an OpenClaw wrapper image with Linuxbrew and development tooling, published to GHCR with automated releases.',
                    organization: 'Ashok Barua',
                    type: 'Project',
                    date: 'May 15, 2026',
                    tags: ['Projects', 'Infrastructure', 'AI'],
                    url: 'https://github.com/ashokbaruaakas/clawkit',
                    secondaryUrl: 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                    metric: '700+ image pulls',
                ),
                new OpenSourceContributionDTO(
                    title: 'LaraVibe-Vue',
                    description: 'Built a modern Laravel 13 starter kit with Vue 3, Inertia.js v3, SSR, authentication, and type-safe route helpers.',
                    organization: 'The SoftPulze',
                    type: 'Project',
                    date: 'August 22, 2025',
                    tags: ['Projects', 'Laravel', 'Vue'],
                    url: 'https://github.com/thesoftpulze/laravibe-vue',
                ),
                new OpenSourceContributionDTO(
                    title: 'Inertia Laravel: inertiaProps',
                    description: 'Introduced the inertiaProps testing helper with nested dot-notation support for clearer Inertia response assertions.',
                    organization: 'Inertia.js',
                    type: 'Pull Request',
                    date: 'June 5, 2025',
                    tags: ['Pull Requests', 'Laravel', 'Developer Tools'],
                    url: 'https://github.com/inertiajs/inertia-laravel/pull/700',
                ),
                new OpenSourceContributionDTO(
                    title: 'The SoftPulze',
                    description: 'Started The SoftPulze as an organization for open-source and private software projects.',
                    organization: 'The SoftPulze',
                    type: 'Organization',
                    date: 'January 17, 2025',
                    tags: ['Projects', 'Developer Tools'],
                    url: 'https://github.com/thesoftpulze',
                ),
                new OpenSourceContributionDTO(
                    title: 'DevPulse CLI',
                    description: 'Started an open-source PHP CLI for standardizing development scripts and server workflows.',
                    organization: 'The SoftPulze',
                    type: 'Project',
                    date: 'December 1, 2024',
                    tags: ['Projects', 'Developer Tools', 'Infrastructure'],
                    url: 'https://github.com/thesoftpulze/devpulse-cli',
                ),
                new OpenSourceContributionDTO(
                    title: 'Minor OSS contributions',
                    description: 'Small contributions to Raycast Extensions, including Paste in Active App for Raycast Ollama and a Brew cleanup command.',
                    organization: 'Raycast Extensions',
                    type: 'Contributions',
                    date: '2024 — 2026',
                    tags: ['Contributions', 'Raycast', 'Developer Tools'],
                    url: 'https://github.com/raycast/extensions',
                ),
            ],
            experience: [
                new ExperienceDTO(
                    role: 'Senior Full-Stack Engineer',
                    company: 'Grow More Gaze',
                    url: 'https://growmoregaze.com',
                    description: 'The platforms that run an international link-building agency: ordering, invoicing, payments, email campaigns and HR/payroll.',
                    period: 'Oct 2023 — Present',
                    highlights: [
                        'Joined to fill the company\'s senior engineering role; own all of its technology, from architecture and code to servers, security and deployments',
                        'Built and run the agency\'s ordering portal, invoicing, payments, email campaigns and HR/payroll, plus several internal projects',
                        'Integrated 7 payment gateways with idempotent webhook handling, so retries and duplicate callbacks never double-charge',
                        'Run CI/CD with zero-downtime deploys, server hardening and monitoring',
                        'Agent-first workflow: plan first on complex work, one isolated branch and environment per issue, automated review by my own review-agent skill, then line-by-line human review, with extra care on payment and auth code',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Independent Contractor and Open Source Maintainer',
                    company: 'Self-employed',
                    description: '',
                    period: 'May 2026 — Present (alongside Grow More Gaze)',
                    highlights: [
                        'Contracted directly by the client to build bizztechsz.com, a platform for worldwide bulk-order quotes and company portfolios; built to the client\'s requirements and still live, and I continue to maintain and extend it',
                        'Maintain open-source Laravel and DevOps tooling (LaraVibe Standards, LaraVibe-Vue, clawkit with 700+ image pulls, Notify Fanout, Proton Pass Secret Fetcher) and contribute upstream to inertia-laravel and Raycast',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Senior Software Engineer, Team Lead of 6 (last 4 months)',
                    company: 'Softrobotics Bangladesh Ltd',
                    description: 'Core payments engineer on SiPay, a licensed e-money and payment platform in Türkiye built by Softrobotics.',
                    url: 'https://sipay.com.tr/en/',
                    period: 'Sep 2021 — Sep 2023',
                    highlights: [
                        'Led a team of six engineers: sprint planning, code reviews, hiring interviews, and balancing workload so the team kept delivering and stayed happy',
                        'Brought an engineer who had asked to leave another team into mine; through one-to-one support he became a fully engaged contributor, and management recognised me for it',
                        'Developed merchant banking, mobile banking, and payment modules for a high-volume production payment platform',
                        'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                        'Built and maintained microservices instrumented with Elasticsearch and Graylog, making production incidents traceable across services',
                        'Built the platform side of the HSM integration for PCI-DSS-aligned saved-card storage, encryption, and card management',
                        'Drove core parts of the legacy upgrade from PHP 7.4 and an older Laravel release to PHP 8.2 and a modern Laravel version',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-Stack Web Developer & Designer',
                    company: 'Stellar BD Ltd',
                    url: 'https://stellarbd.com',
                    description: 'Built HRM/payroll systems wired to IoT attendance hardware, plus client web apps.',
                    period: 'Oct 2019 — Aug 2021',
                    highlights: [
                        'Built core HRM, payroll, and shift modules integrated with fingerprint scanners for a large, multi-site workforce',
                        'Implemented real-time attendance sync via MQTT across the company\'s attendance devices, replacing nightly manual imports',
                        'Maintained and extended the HRM on the legacy Web2py (Python) framework',
                        'Built client websites and automation scripts in PHP, JavaScript, and Python',
                        'Designed responsive UIs and managed hosting end-to-end',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-Stack Web Developer',
                    company: 'Multiplex Web Design',
                    description: 'Delivered custom web apps for SMB clients, end-to-end.',
                    period: 'Mar 2018 — Sep 2019',
                    highlights: [
                        'Owned full project lifecycles for SMB clients, from requirements through deployment',
                        'Delivered full-stack features with PHP, JavaScript, MySQL, and responsive design',
                        'Worked directly with clients on scoping, timelines, and delivery',
                    ],
                ),
            ],
            education: [
                new EducationDTO(
                    degree: 'BSc in Computer Science (evening program, while working full-time)',
                    school: 'East Delta University - Chattogram, Bangladesh',
                    period: '2018 — 2021',
                ),
                new EducationDTO(
                    degree: 'Diploma in Computer Science',
                    school: 'Bangladesh Sweden Polytechnic Institute - Kaptai, Rangamati, Bangladesh',
                    period: '2013 — 2017',
                ),
            ],
            languages: [
                new LanguageDTO(name: 'Bengali', level: 'Native'),
                new LanguageDTO(name: 'English', level: 'Fluent'),
            ],
            familiarSkills: ['React (with Inertia)', 'Next.js', 'Go', 'Rust'],
            professionalSummary: 'Senior Full-Stack Engineer with 7+ years building payment systems and business platforms in Laravel, PHP 8, Vue 3 and TypeScript. Led a team of six engineers on SiPay, a licensed e-money platform in Türkiye, where I built HSM-backed saved-card storage and drove the PHP 7.4 to 8.2 upgrade. Since 2023 I have been the senior engineer at Grow More Gaze, owning its platforms, servers and security end to end. I work agent-first and review every change that touches money, data or auth myself.',
            websiteUrl: 'https://ashokbaruaakas.com',
            caseStudies: [
                new CaseStudyDTO(
                    title: 'Duplicate-safe payment collection across 7 gateways',
                    context: 'Grow More Gaze · Senior Full-Stack Engineer · 2023 — Present',
                    problem: 'The agency collects payments from clients worldwide through 7 gateways with very different webhook behaviors. Retried or duplicated webhooks risked double-counting payments or leaving invoices in the wrong state, and every new gateway threatened a checkout rewrite.',
                    constraints: [
                        'Solo-built and operated, so the pipeline had to stay simple to debug and extend',
                        'Gateways differ in webhook retry policies, signatures, and settlement flows',
                        'Every invoice must reflect exactly one settled payment',
                    ],
                    design: [
                        'A normalized payment pipeline: each gateway sits behind an adapter that feeds one internal payment state machine',
                        'Idempotency keys and webhook deduplication make retried or double-delivered webhooks no-ops',
                        'Signature verification and amount/currency cross-checks run before any invoice is marked paid',
                        'Queue-based webhook processing, so a slow or unreachable gateway never blocks checkout',
                    ],
                    tradeoffs: [
                        'A gateway-agnostic core means gateway-specific features live in adapters instead of the main flow',
                        'Async webhook settlement finalizes invoices moments after checkout rather than inline with the redirect',
                    ],
                    results: [
                        'Duplicate webhook deliveries are absorbed without duplicate charges or corrupted invoice states',
                        'Adding a gateway became an adapter instead of a rewrite — 7 live today, including bKash and Nagad',
                        'Payment issues are traceable end-to-end through one pipeline instead of 7 separate integrations',
                    ],
                    technologies: ['Laravel', 'MySQL', 'Redis', 'Horizon', 'Webhooks'],
                ),
                new CaseStudyDTO(
                    title: 'HSM-backed saved cards for a payment platform',
                    context: 'SiPay · Core payments engineer · 2021 — 2023',
                    problem: 'Merchants needed saved cards for repeat payments, but card data could never be stored or handled in plaintext. The HSM provided the cryptography; SiPay needed a dependable way to use it on the live payment path.',
                    constraints: [
                        'Card numbers must never be readable in application storage or logs',
                        'Saving and charging cards sits on the live payment path, so the integration had to be fast and reliable',
                        'The HSM is a vendor device with its own protocol and only basic documentation',
                    ],
                    design: [
                        'Built the SiPay-side integration that communicates with the HSM to encrypt, decrypt, and manage card data',
                        'Merchants and other services work with card references instead of raw card numbers',
                        'Handled the saved-card lifecycle end to end: save, list, charge, and delete',
                        'Card data kept out of the logs shipped to Graylog',
                    ],
                    tradeoffs: [
                        'Relied on proven vendor hardware for cryptography instead of building it in software, accepting the device\'s protocol and limits',
                        'Every save and charge depends on the HSM, so its availability became part of the payment path',
                    ],
                    results: [
                        'Merchants got saved-card payments without card data ever being stored in plaintext',
                        'Card security handled by dedicated hardware, supporting SiPay\'s PCI-DSS-aligned approach',
                    ],
                    technologies: ['Laravel', 'HSM', 'Microservices', 'Graylog'],
                ),
            ],
            testimonials: [
                new TestimonialDTO(
                    quote: 'Ashok built the platform our whole agency runs on — ordering, invoicing, payments, and payroll — and he runs it on his own. He thinks about failure modes before they happen, and handles everything from architecture to servers.',
                    author: 'Md. Shehad',
                    role: 'Founder & CEO',
                    company: 'Grow More Gaze',
                ),
                new TestimonialDTO(
                    quote: 'On SiPay, Ashok owned critical payment work end to end, from saved-card security to everyday payment flows. He held a high bar for code quality, and when we needed a team lead, he stepped up and led the team through delivery.',
                    author: 'Rajib Deb',
                    role: 'CEO',
                    company: 'Softrobotics Bangladesh Ltd',
                ),
                new TestimonialDTO(
                    quote: 'Ashok and I worked side by side on SiPay\'s payment modules. He takes ownership of hard problems, writes code that\'s easy to review, and played a big part in moving our codebase to PHP 8.2.',
                    author: 'Md. Amanot Ali',
                    role: 'Senior Software Engineer & Team Lead',
                    company: 'Softrobotics Bangladesh Ltd',
                ),
                new TestimonialDTO(
                    quote: 'When a payment flow broke in production, Ashok was one of the first people we called. He digs into the logs, finds the real cause, and explains the fix clearly — and he helps the engineers around him grow.',
                    author: 'Md. Arafat Uddin',
                    role: 'Senior Software Engineer & Team Lead',
                    company: 'Softrobotics Bangladesh Ltd',
                ),
            ],
        );
    }
}
