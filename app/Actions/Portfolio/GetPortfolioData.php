<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\EducationDTO;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\LanguageDTO;
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
            bio: 'Full-stack developer with 7+ years shipping production web apps in Laravel, Vue, and TypeScript. I cut my teeth on enterprise payment systems, microservices, and HSM-secured platforms — and now I’m building a 40,000-user SaaS from the ground up while weaving AI-augmented workflows into how I ship. I like pragmatic, well-crafted software and the occasional terminal sparkle.',
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
                    name: 'bizztechsz.com',
                    description: 'Client business platform for worldwide bulk-order quotes and company portfolios. Built and maintained part-time alongside a full-time role.',
                    owner: 'bizztechsz',
                    repo: 'bizztechsz.com',
                    technologies: ['Laravel', 'Vue.js', 'TypeScript', 'Tailwind', 'MySQL'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://bizztechsz.com',
                ),
                new ProjectDTO(
                    name: 'laravibe-vue',
                    description: 'Open-source Laravel 13 starter kit with Vue 3 + Inertia.js v3 — auth, SSR, admin area.',
                    owner: 'softpulze',
                    repo: 'laravibe-vue',
                    technologies: ['Laravel 13', 'Vue 3', 'Inertia.js', 'TypeScript', 'Tailwind'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://github.com/softpulze/laravibe-vue',
                ),
                new ProjectDTO(
                    name: 'clawkit',
                    description: 'OpenClaw wrapper image with Linuxbrew + dev tooling, published to GHCR via automated CI/CD.',
                    owner: 'ashokbaruaakas',
                    repo: 'clawkit',
                    technologies: ['Docker', 'GitHub Actions', 'CI/CD', 'OpenClaw'],
                    stars: 1,
                    language: 'Dockerfile',
                    demoUrl: 'https://github.com/ashokbaruaakas/clawkit',
                ),
                new ProjectDTO(
                    name: 'devpulse-cli',
                    description: 'DevPulseCLI: your shortcut through the development grind.',
                    owner: 'softpulze',
                    repo: 'devpulse-cli',
                    technologies: ['PHP', 'CLI'],
                    stars: 1,
                    language: 'PHP',
                    demoUrl: 'https://github.com/softpulze/devpulse-cli',
                ),
                new ProjectDTO(
                    name: 'laravel-authorize-attribute',
                    description: 'Attribute-based authorization for Laravel — declare permission checks as PHP attributes.',
                    owner: 'ashokbaruaakas',
                    repo: 'laravel-authorize-attribute',
                    technologies: ['PHP', 'Laravel'],
                    stars: 0,
                    language: 'PHP',
                    demoUrl: 'https://github.com/ashokbaruaakas/laravel-authorize-attribute',
                ),
                new ProjectDTO(
                    name: 'proton-pass-action',
                    description: 'GitHub Action for integrating Proton Pass secrets into CI/CD workflows.',
                    owner: 'ashokbaruaakas',
                    repo: 'proton-pass-action',
                    technologies: ['TypeScript', 'GitHub Actions'],
                    stars: 2,
                    language: 'TypeScript',
                    demoUrl: 'https://github.com/ashokbaruaakas/proton-pass-action',
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
                    description: 'Shipped SiPay, a banking platform for top-tier Turkish financial institutions.',
                    period: 'Sep 2021 — Sep 2023',
                    highlights: [
                        'Developed merchant banking, mobile banking, and comprehensive payment modules',
                        'Built diverse payment flows: link, P2P/B2B, QR, bill pay, POS, cash out, deposit/withdraw',
                        'Scaled backend services on microservices with Elasticsearch + Graylog logging',
                        'Integrated HSM devices for secure crypto operations and key management',
                        'Led the team for the final 4 months — code reviews and mentoring',
                    ],
                ),
                new ExperienceDTO(
                    role: 'Full-stack Web Developer & Designer',
                    company: 'Stellar BD Ltd',
                    description: 'Built HRM/payroll systems wired to IoT hardware and client web apps.',
                    period: 'Oct 2019 — Aug 2021',
                    highlights: [
                        'HRM & payroll integrated with fingerprint scanners, temperature sensors, smart locks, notice boards',
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
                    school: 'East Delta University, Chittagong',
                    period: '2018 — 2021',
                ),
                new EducationDTO(
                    degree: 'Diploma in Computer Science',
                    school: 'Bangladesh Sweden Polytechnic Institute, Rangamati',
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
