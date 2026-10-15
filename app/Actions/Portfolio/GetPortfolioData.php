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
            tagline: 'Senior Full-Stack Engineer — Fintech & Multi-Tenant SaaS',
            bio: "I build scalable, production-ready web applications with **PHP, Laravel, TypeScript, and Vue** — from backend architecture and APIs to deployment and production operations.\n\n**8+ years building real-world systems, including SaaS platforms, payment infrastructure, and enterprise applications.** Open to remote roles from UTC+6.",
            location: 'Chattogram, Bangladesh',
            availability: 'Open to remote',
            currentWork: 'Grow More Gaze — a multi-tenant SaaS platform serving 40,000+ customers (payments, payroll, HRM)',
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
                new SkillCategoryDTO(category: 'Frameworks', items: ['Laravel', 'Vue.js', 'Nuxt.js', 'React', 'Next.js', 'Inertia.js', 'Tailwind CSS', 'Livewire', 'Alpine.js']),
                new SkillCategoryDTO(category: 'Backend & Architecture', items: ['REST API design', 'System design', 'Queues & Horizon', 'Webhooks', 'Idempotency', 'PCI-DSS', 'Observability']),
                new SkillCategoryDTO(category: 'Databases', items: ['MySQL', 'PostgreSQL', 'Redis']),
                new SkillCategoryDTO(category: 'Practices', items: ['Testing (PHPUnit, Pest)', 'Code review', 'Mentoring', 'Agile']),
                new SkillCategoryDTO(category: 'DevOps & Tools', items: ['Linux', 'Nginx', 'Docker', 'Git', 'GitHub Actions', 'CI/CD', 'Elasticsearch', 'Graylog', 'MQTT', 'Deployer']),
                new SkillCategoryDTO(category: 'AI & Agent Engineering', items: ['OpenClaw (self-hosted AI agent runtime)', 'MCP', 'LLM APIs', 'Prompt Engineering']),
            ],
            projects: [
                new ProjectDTO(
                    name: 'SiPay',
                    description: 'A banking payment platform built for institutional clients, with mobile banking, merchant banking, payment gateways, card saving, and secure key management.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Fintech', 'Microservices', 'Elasticsearch', 'Graylog', 'HSM'],
                    stars: 0,
                    language: 'PHP',
                    tier: 1,
                    period: '2021 — 2023',
                    role: 'Core payments engineer, promoted to team lead for the final four months.',
                    highlights: [
                        'Mobile banking, merchant banking, and multiple payment gateway integrations',
                        'Card saving system with HSM-backed key and crypto management',
                        'Microservices architecture with Elasticsearch and Graylog for logging and monitoring',
                    ],
                    metric: 'Team lead (final 4 mo)',
                    demoUrl: 'https://sipay.com.tr/en/',
                    linkLabel: 'Product',
                ),
                new ProjectDTO(
                    name: 'Grow More Gaze',
                    description: 'A multi-tenant SaaS platform serving 40,000+ customers across HRM, payroll, invoicing, and payments.',
                    owner: null,
                    repo: null,
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'MySQL', 'CI/CD'],
                    stars: 0,
                    language: 'PHP',
                    tier: 1,
                    period: '2023 — Present',
                    role: 'Own the full lifecycle: architecture, database design, servers, CI/CD, and technical planning.',
                    highlights: [
                        'Multi-tenant HRM platform',
                        'Payroll and invoicing workflows',
                        'Multi-tenant architecture with isolated data per business',
                        'Multi-gateway payments including bKash, Nagad, and other providers',
                    ],
                    metric: '40,000+ customers',
                    demoUrl: 'https://growmoregaze.com',
                    linkLabel: 'Product',
                ),
                new ProjectDTO(
                    name: 'Stellar BD — High-Volume HRM System',
                    description: 'An HRM system handling attendance management, full payroll, and shift management at scale.',
                    owner: null,
                    repo: null,
                    technologies: ['Python', 'MySQL', 'MQTT', 'IoT'],
                    stars: 0,
                    language: 'Python',
                    tier: 1,
                    period: '2019 — 2021',
                    role: 'Built the core HRM, payroll, and shift modules and integrated fingerprint hardware via MQTT.',
                    highlights: [
                        'Attendance management and full payroll workflows',
                        'Fingerprint and attendance hardware synchronized through MQTT',
                    ],
                    demoUrl: 'https://stellarbd.com',
                    linkLabel: 'Company',
                ),
                new ProjectDTO(
                    name: 'bizztechsz.com',
                    description: 'A live client platform for worldwide bulk-order quotes and company portfolios, used in production.',
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
                    description: 'An open-source Laravel starter kit with Vue 3 and Inertia.js that I maintain.',
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
                    metric: '500+ image pulls',
                    isPublicRepo: true,
                    secondaryUrl: 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                    secondaryLabel: 'GHCR',
                ),
            ],
            openSourceContributions: [
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
                    metric: '500+ image pulls',
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
                    role: 'Senior Full-Stack Engineer / Technical Lead',
                    company: 'Grow More Gaze',
                    url: 'https://growmoregaze.com',
                    description: 'Technical lead for a multi-tenant SaaS platform serving 40,000+ customers across 1,200+ business tenants, covering payments, payroll, and HRM.',
                    period: 'Oct 2023 — Present',
                    highlights: [
                        'Built the core platform from scratch — org portfolio, HRM, payroll, invoicing, and internal email campaigns — now processing 25,000+ payroll runs a month',
                        'Integrated 7 payment gateways (PayPal, Stripe, Paddle, crypto, PayProGlobal, bKash, Nagad) behind idempotent webhook handling, cutting duplicate-charge tickets by 90%',
                        'Own the full lifecycle: architecture, Laravel/Vue/TypeScript dev, DB design, servers, and CI/CD; moved releases from weekly to daily with zero-downtime deploys',
                        'Lead a team of 5 engineers: technical planning, code review, and mentoring 2 junior developers to mid-level',
                        'Weave AI agent workflows (OpenClaw, LLM APIs) into development and operations',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Senior Software Engineer',
                    company: 'Softrobotics Bangladesh Ltd',
                    description: 'Core payments engineer on SiPay, a banking payment platform built for institutional clients.',
                    url: 'https://sipay.com.tr/en/',
                    period: 'Sep 2021 — Sep 2023',
                    highlights: [
                        'Developed merchant banking, mobile banking, and payment modules handling 150,000+ transactions a day',
                        'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                        'Scaled backend services on microservices with Elasticsearch + Graylog observability, cutting incident triage time by 60%',
                        'Integrated HSM devices for PCI-DSS-aligned card storage, crypto operations, and key management',
                        'Promoted to team lead for the final 4 months, leading 6 engineers through delivery of the core payment flows',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-Stack Web Developer & Designer',
                    company: 'Stellar BD Ltd',
                    url: 'https://stellarbd.com',
                    description: 'Built HRM/payroll systems wired to IoT hardware and client web apps.',
                    period: 'Oct 2019 — Aug 2021',
                    highlights: [
                        'Built core HRM, payroll, and shift modules integrated with fingerprint scanners for 8,000+ employees',
                        'Real-time IoT sync via MQTT across 40+ attendance devices, replacing nightly manual imports',
                        'Client websites and automation scripts in PHP, JavaScript, Python, and Web2py',
                        'Designed responsive UIs and managed hosting end-to-end',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-Stack Web Developer',
                    company: 'Multiplex Web Design',
                    description: 'Delivered custom web apps for SMB clients, end-to-end.',
                    period: 'Mar 2018 — Sep 2019',
                    highlights: [
                        'Handled full project lifecycles for 20+ client projects, from requirements through deployment',
                        'Full-stack with PHP, JavaScript, MySQL, and responsive design',
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
            familiarSkills: ['Go', 'Rust'],
            professionalSummary: 'Senior full-stack engineer and technical lead (Laravel, PHP 8, Vue 3, TypeScript) with 8+ years shipping payment and SaaS systems. '
                .'Multi-tenant SaaS: Built the Grow More Gaze core platform from scratch and lead its 5-person team, serving 40,000+ customers with isolated tenant data and multi-gateway payments. '
                .'Fintech and payments: Core payments engineer and later team lead on SiPay for Turkish banking clients, handling 150,000+ transactions a day across merchant and mobile banking, HSM-backed cryptographic operations, and PCI-DSS-aligned card saving. '
                .'Full lifecycle: Own system design, REST APIs, queues, testing, CI/CD, and production observability. Open to senior or lead remote roles.',
            websiteUrl: 'https://ashokbaruaakas.com',
            caseStudies: [
                new CaseStudyDTO(
                    title: 'Tenant isolation for a 40,000-customer SaaS',
                    context: 'Grow More Gaze · Technical lead · 2023 — Present',
                    problem: 'Hundreds of businesses share one platform for payroll, HRM, and payments. A single query that forgets its tenant scope would leak salary or payment data between companies.',
                    constraints: [
                        'One small team, so the model had to be simple enough for every engineer to apply correctly',
                        'Shared infrastructure to keep hosting costs predictable',
                        'Per-tenant exports and deletion on request',
                    ],
                    design: [
                        'Single database with a tenant key on every business table, enforced by a global Eloquent scope that fails closed when no tenant is resolved',
                        'Tenant resolved once per request and per queued job, and carried into Horizon workers explicitly',
                        'Pest architecture tests that fail the build when a tenant-owned model skips the scope',
                    ],
                    tradeoffs: [
                        'Chose a shared schema over database-per-tenant: cheaper and simpler migrations, at the cost of relying on application-level enforcement',
                        'Accepted slightly heavier composite indexes (tenant_id first) to keep scoped queries fast',
                    ],
                    results: [
                        'Zero cross-tenant data incidents since launch',
                        'Onboarding a new business went from a manual half-day setup to under 5 minutes',
                        'p95 page response time held under 300 ms as tenants grew past 1,200',
                    ],
                    technologies: ['Laravel', 'MySQL', 'Horizon', 'Pest', 'Vue.js'],
                ),
                new CaseStudyDTO(
                    title: 'HSM-backed card vault for a banking payment platform',
                    context: 'SiPay · Core payments engineer, later team lead · 2021 — 2023',
                    problem: 'Merchants needed saved cards for repeat payments, but storing card data put the whole platform in PCI-DSS scope and made every service a potential leak.',
                    constraints: [
                        'Card numbers could never be readable by application code or logs',
                        'Strict latency budget on the payment path',
                        'Banking clients required auditable key rotation',
                    ],
                    design: [
                        'A small, isolated vault service that alone talks to the HSM; every other service only ever sees opaque tokens',
                        'Envelope encryption: data keys wrapped by HSM-held master keys, rotated without re-encrypting every card at once',
                        'Idempotency keys on every tokenize and charge call so retries never double-charge',
                        'Structured logs to Graylog with card data masked at the logger level',
                    ],
                    tradeoffs: [
                        'An extra network hop for each tokenize call, in exchange for a much smaller PCI-DSS scope',
                        'Kept the vault deliberately feature-poor so it was easy to audit',
                    ],
                    results: [
                        'PCI-DSS scope reduced to one service instead of the whole platform',
                        'Tokenization added under 20 ms at p95',
                        'Key rotation went from a planned maintenance window to a routine, zero-downtime job',
                    ],
                    technologies: ['Laravel', 'HSM', 'Microservices', 'Elasticsearch', 'Graylog'],
                ),
            ],
            testimonials: [
                new TestimonialDTO(
                    quote: 'Ashok took our platform from an idea to something tens of thousands of customers rely on. He thinks about failure modes before they happen, and the team writes better code because of his reviews.',
                    author: 'Placeholder Name',
                    role: 'Founder & CEO',
                    company: 'Grow More Gaze',
                ),
                new TestimonialDTO(
                    quote: 'When we moved card storage behind the HSM, Ashok was the person who made it work end to end. Calm under pressure, careful with security, and always clear about trade-offs.',
                    author: 'Placeholder Name',
                    role: 'Engineering Manager',
                    company: 'Softrobotics Bangladesh Ltd',
                ),
            ],
        );
    }
}
