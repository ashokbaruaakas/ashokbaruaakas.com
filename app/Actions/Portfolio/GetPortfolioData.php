<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\CaseStudyDTO;
use App\DTOs\Portfolio\EducationDTO;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\LanguageDTO;
use App\DTOs\Portfolio\OpenSourceContributionDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;
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
            about: 'I\'m a senior full-stack engineer who likes owning systems end to end — from the database schema and payment webhooks to the servers they run on. Most of my career has been on software that moves money: payment flows and HSM-backed saved cards at SiPay, and today the platform that runs Grow More Gaze, where I\'m the sole engineer. I care about quiet reliability — idempotent payments, tests that catch regressions, and deploys nobody notices. I\'m looking for a senior or lead role on a product team, remote or with relocation.',
            sideProjects: 'Open-source at @thesoftpulze · AI agent workflows',
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
                new SkillCategoryDTO(category: 'Practices', items: ['Testing (PHPUnit, Pest)', 'Code review', 'Mentoring', 'Agile']),
                new SkillCategoryDTO(category: 'DevOps & Tools', items: ['Linux', 'Nginx', 'Docker', 'Git', 'GitHub Actions', 'CI/CD', 'Elasticsearch', 'Graylog', 'MQTT', 'Deployer']),
                new SkillCategoryDTO(category: 'AI & Agent Engineering', items: ['OpenClaw (self-hosted AI agent runtime)', 'MCP', 'LLM APIs', 'Prompt Engineering']),
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
                    role: 'Sole engineer — own the full lifecycle: architecture, database design, servers, CI/CD, and technical planning.',
                    metric: 'Sole engineer',
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
                    description: 'A freelance client platform for worldwide bulk-order quotes and company portfolios, scoped directly with the client and still maintained in production.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'Tailwind CSS', 'MySQL'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://bizztechsz.com',
                    isPublicRepo: false,
                ),
                new ProjectDTO(
                    name: 'LaraVibe-Vue',
                    description: 'An actively maintained open-source Laravel starter kit with Vue 3 and Inertia.js.',
                    owner: 'thesoftpulze',
                    repo: 'laravibe-vue',
                    technologies: ['Laravel', 'Vue.js', 'Inertia.js', 'TypeScript', 'Tailwind CSS'],
                    stars: 2,
                    language: 'PHP',
                    demoUrl: 'https://github.com/thesoftpulze/laravibe-vue',
                    isPublicRepo: true,
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
                    secondaryUrl: 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                    secondaryLabel: 'GHCR',
                ),
                new ProjectDTO(
                    name: 'Notify Fanout',
                    description: 'A GitHub Action that sends CI/CD notifications to one or many chats from a single workflow step. v1 supports Telegram; webhook, Slack, and more providers are planned.',
                    owner: 'ashokbaruaakas',
                    repo: 'notify-fanout-action',
                    technologies: ['TypeScript', 'GitHub Actions', 'CI/CD', 'Telegram Bot API'],
                    stars: 0,
                    language: 'TypeScript',
                    demoUrl: 'https://github.com/ashokbaruaakas/notify-fanout-action',
                    isPublicRepo: true,
                    secondaryUrl: 'https://github.com/marketplace/actions/notify-fanout',
                    secondaryLabel: 'Marketplace',
                ),
            ],
            openSourceContributions: [
                new OpenSourceContributionDTO(
                    title: 'Notify Fanout',
                    description: 'Built and published a GitHub Action that fans out CI/CD notifications to multiple chats from one step, with event presets, templates, per-target settings, and retries. v1 ships with Telegram; the provider-based design is ready for webhook, Slack, and more.',
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
                    description: 'Sole engineer behind the platform that runs a link-building agency end-to-end — customer ordering, invoicing, multi-gateway payment collection, email campaigns, and staff HRM/payroll — for clients worldwide.',
                    period: 'Oct 2023 — Present',
                    highlights: [
                        'Designed, built, and operate solo the platform the agency runs on day to day — customer ordering portal, invoicing, email campaigns, and staff HRM/payroll',
                        'Integrated 7 payment gateways (PayPal, Stripe, Paddle, crypto, PayProGlobal, bKash, Nagad) behind idempotent webhook handling for duplicate-safe payment collection',
                        'Own the full lifecycle: architecture, Laravel/Vue/TypeScript development, database design, VPS and server operations, automation, and CI/CD with zero-downtime deploys',
                        'Built the in-house email campaign system that powers the agency\'s outreach and customer marketing',
                        'Integrate AI agent workflows (LLM APIs, MCP) into day-to-day development and operations',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Senior Software Engineer / Team Lead',
                    company: 'Softrobotics Bangladesh Ltd',
                    description: 'Core payments engineer on SiPay, a licensed e-money and payment platform in Türkiye that Softrobotics builds and supports, mainly from its Bangladesh team.',
                    url: 'https://sipay.com.tr/en/',
                    period: 'Sep 2021 — Sep 2023',
                    highlights: [
                        'Developed merchant banking, mobile banking, and payment modules for a high-volume production payment platform',
                        'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                        'Built and maintained microservices instrumented with Elasticsearch and Graylog, making production incidents traceable across services',
                        'Built the platform side of the HSM integration for PCI-DSS-aligned saved-card storage, encryption, and card management',
                        'Drove core parts of the legacy upgrade from PHP 7.4 and an older Laravel release to PHP 8.2 and a modern Laravel version',
                        'Promoted to team lead for the final 4 months, leading 6 engineers through delivery of core payment flows with code review and mentoring',
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
            professionalSummary: 'Senior full-stack engineer (Laravel, PHP 8, Vue 3, TypeScript) with 7+ years shipping payment systems and business platforms. '
                .'Sole engineer behind the platform that runs Grow More Gaze, a link-building agency serving clients worldwide, from ordering and multi-gateway payments to payroll and HRM. '
                .'Previously core payments engineer and team lead on SiPay, a licensed e-money platform in Türkiye, delivering HSM-backed saved cards and the legacy codebase upgrade to PHP 8.2. '
                .'Own the full lifecycle, from system design and REST APIs to testing, CI/CD, and production observability. Open to senior or lead roles, remote or with relocation.',
            websiteUrl: 'https://ashokbaruaakas.com',
            caseStudies: [
                new CaseStudyDTO(
                    title: 'Duplicate-safe payment collection across 7 gateways',
                    context: 'Grow More Gaze · Sole engineer · 2023 — Present',
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
