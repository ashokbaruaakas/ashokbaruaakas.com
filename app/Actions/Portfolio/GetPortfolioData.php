<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\EducationDTO;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\LanguageDTO;
use App\DTOs\Portfolio\OpenSourceContributionDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\DTOs\Portfolio\SkillCategoryDTO;
use App\DTOs\Portfolio\SocialLinkDTO;

class GetPortfolioData
{
    /**
     * Build the portfolio data used by public pages.
     */
    public function handle(): PortfolioDTO
    {
        return new PortfolioDTO(
            name: 'Ashok Barua Akas',
            tagline: 'Full-Stack Engineer · PHP · Laravel · TypeScript · Vue · Go',
            bio: "I build scalable, production-ready web applications with **PHP, Laravel, TypeScript, and Vue** — from backend architecture and APIs to deployment and production operations.\n\n**7+ years building real-world systems, including SaaS platforms, payment infrastructure, and enterprise applications.**",
            location: 'Chattogram, Bangladesh',
            currentWork: 'Grow More Gaze — a 40,000-user SaaS platform (payments, payroll, HRM)',
            sideProjects: 'Open-source at @softpulze · AI agent workflows',
            githubUsername: 'ashokbaruaakas',
            organization: 'softpulze',
            email: 'ashokbaruaakas@gmail.com',
            phone: '+8801829853914',
            socialLinks: [
                new SocialLinkDTO(platform: 'GitHub', url: 'https://github.com/ashokbaruaakas', icon: 'github'),
                new SocialLinkDTO(platform: 'X', url: 'https://twitter.com/ashokbaruaakas', icon: 'twitter'),
                new SocialLinkDTO(platform: 'Telegram', url: 'https://t.me/ashokbaruaakas', icon: 'send'),
                new SocialLinkDTO(platform: 'WhatsApp', url: 'https://wa.me/+8801829853914', icon: 'message-circle'),
                new SocialLinkDTO(platform: 'Discord', url: 'https://discordapp.com/users/611991650868133894', icon: 'message-square'),
            ],
            skills: [
                new SkillCategoryDTO(category: 'Languages', items: ['PHP', 'TypeScript', 'JavaScript', 'Go', 'Rust', 'Python', 'SQL', 'HTML', 'CSS']),
                new SkillCategoryDTO(category: 'Frameworks', items: ['Laravel', 'Vue.js', 'Nuxt.js', 'React', 'Next.js', 'Inertia.js', 'Tailwind CSS', 'Livewire', 'Alpine.js']),
                new SkillCategoryDTO(category: 'Databases', items: ['MySQL', 'PostgreSQL', 'Redis']),
                new SkillCategoryDTO(category: 'DevOps & Tools', items: ['Linux', 'Nginx', 'Docker', 'Git', 'GitHub Actions', 'VPS', 'CI/CD', 'Elasticsearch', 'Graylog', 'Composer', 'VS Code', 'MQTT', 'Deployer', 'Certbot']),
                new SkillCategoryDTO(category: 'AI & Agent Engineering', items: ['OpenClaw', 'MCP', 'LLM APIs', 'Prompt Engineering', 'AI-Augmented Development']),
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
                    role: 'Led development of core payment flows and served as team lead for the final four months.',
                    highlights: [
                        'Mobile banking, merchant banking, and multiple payment gateway integrations',
                        'Card saving system with HSM-backed key and crypto management',
                        'Microservices architecture with Elasticsearch and Graylog for logging and monitoring',
                    ],
                    metric: 'Team Lead',
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
                    technologies: ['Web2py', 'MySQL', 'MQTT', 'IoT'],
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
                    owner: 'bizztechsz',
                    repo: 'bizztechsz.com',
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'Tailwind', 'MySQL'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://bizztechsz.com',
                    isPublicRepo: false,
                ),
                new ProjectDTO(
                    name: 'LaraVibe-Vue',
                    description: 'An open-source Laravel starter kit with Vue 3 and Inertia.js that I maintain.',
                    owner: 'softpulze',
                    repo: 'laravibe-vue',
                    technologies: ['Laravel 13', 'Vue 3', 'Inertia.js', 'TypeScript', 'Tailwind'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://github.com/softpulze/laravibe-vue',
                    isPublicRepo: true,
                ),
                new ProjectDTO(
                    name: 'clawkit',
                    description: 'A Docker and AI tooling wrapper for OpenClaw, published to GHCR through automated CI/CD.',
                    owner: 'ashokbaruaakas',
                    repo: 'clawkit',
                    technologies: ['Docker', 'GitHub Actions', 'CI/CD', 'OpenClaw', 'AI'],
                    stars: 1,
                    language: 'Dockerfile',
                    demoUrl: 'https://github.com/ashokbaruaakas/clawkit',
                    metric: '500+ image pulls',
                    isPublicRepo: true,
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
                    organization: 'SoftPulze',
                    type: 'Project',
                    date: 'July 23, 2026',
                    tags: ['Projects', 'Laravel', 'Developer Tools'],
                    url: 'https://github.com/softpulze/laravibe-standards',
                    secondaryUrl: 'https://packagist.org/packages/softpulze/laravibe-standards',
                ),
                new OpenSourceContributionDTO(
                    title: 'Clawkit',
                    description: 'Built an OpenClaw wrapper image with Linuxbrew and development tooling, published to GHCR with automated releases.',
                    organization: 'Ashok Barua Akas',
                    type: 'Project',
                    date: 'May 15, 2026',
                    tags: ['Projects', 'Infrastructure', 'AI'],
                    url: 'https://github.com/ashokbaruaakas/clawkit',
                    secondaryUrl: 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
                    metric: '500+ image pulls',
                ),
                new OpenSourceContributionDTO(
                    title: 'LaraVibe Vue',
                    description: 'Built a modern Laravel 13 starter kit with Vue 3, Inertia.js v3, SSR, authentication, and type-safe route helpers.',
                    organization: 'SoftPulze',
                    type: 'Project',
                    date: 'August 22, 2025',
                    tags: ['Projects', 'Laravel', 'Vue'],
                    url: 'https://github.com/softpulze/laravibe-vue',
                ),
                new OpenSourceContributionDTO(
                    title: 'SoftPulze',
                    description: 'Started SoftPulze as an organization for open-source and private software projects.',
                    organization: 'SoftPulze',
                    type: 'Organization',
                    date: 'January 17, 2025',
                    tags: ['Projects', 'Developer Tools'],
                    url: 'https://github.com/softpulze',
                ),
                new OpenSourceContributionDTO(
                    title: 'DevPulse CLI',
                    description: 'Started an open-source PHP CLI for standardizing development scripts and server workflows.',
                    organization: 'SoftPulze',
                    type: 'Project',
                    date: 'December 1, 2024',
                    tags: ['Projects', 'Developer Tools', 'Infrastructure'],
                    url: 'https://github.com/softpulze/devpulse-cli',
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
                    role: 'Full Stack Developer',
                    company: 'Grow More Gaze',
                    description: 'Building a multi-tenant SaaS platform serving 40,000+ customers across payments, payroll, and HRM.',
                    period: 'Oct 2023 — Present',
                    highlights: [
                        'Built the core platform from scratch — org portfolio, HRM, payroll, invoicing, and internal email campaigns',
                        'Integrated multi-gateway payments: PayPal, Stripe, Paddle, crypto, PayProGlobal, bKash, and Nagad',
                        'Own the full lifecycle: architecture, Laravel/Vue/TypeScript dev, DB design, servers, and CI/CD',
                        'Lead technical planning and set the team’s workflows and best practices',
                        'Weave AI agent workflows (OpenClaw, LLM APIs) into development and operations',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Senior Software Engineer',
                    company: 'Softrobotics Bangladesh Ltd',
                    description: 'Led development of SiPay, a banking payment platform built for institutional clients.',
                    period: 'Sep 2021 — Sep 2023',
                    highlights: [
                        'Developed merchant banking, mobile banking, and comprehensive payment modules',
                        'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                        'Scaled backend services on microservices with Elasticsearch + Graylog logging',
                        'Integrated HSM devices for secure crypto operations and key management',
                        'Led development of core payment flows and served as team lead for the final 4 months',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-stack Web Developer & Designer',
                    company: 'Stellar BD Ltd',
                    description: 'Built HRM/payroll systems wired to IoT hardware and client web apps.',
                    period: 'Oct 2019 — Aug 2021',
                    highlights: [
                        'Built core HRM, payroll, and shift modules integrated with fingerprint scanners',
                        'Real-time IoT sync via MQTT for device communication and management',
                        'Client websites and automation scripts in PHP, JavaScript, Python, and Web2py',
                        'Designed responsive UIs and managed hosting end-to-end',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-stack Web Developer',
                    company: 'Multiplex Web Design',
                    description: 'Delivered custom web apps for SMB clients, end-to-end.',
                    period: 'Mar 2018 — Sep 2019',
                    highlights: [
                        'Handled full project lifecycles: requirements through deployment',
                        'Full-stack with PHP, JavaScript, MySQL, and responsive design',
                        'Worked directly with clients on scoping, timelines, and delivery',
                    ],
                ),
            ],
            education: [
                new EducationDTO(
                    degree: 'BSc in Computer Science',
                    school: 'East Delta University - Chittagong, Bangladesh',
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
        );
    }
}
