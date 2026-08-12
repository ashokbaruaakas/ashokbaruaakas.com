<?php

use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\DTOs\Portfolio\SkillCategoryDTO;
use App\DTOs\Portfolio\SocialLinkDTO;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'portfolio' => fn () => new PortfolioDTO(
        name: 'Ashok Barua Akas',
        tagline: 'Full-Stack Engineer · Laravel · Vue · TypeScript · Go',
        bio: 'Full-stack engineer building products end-to-end with Laravel, Vue, TypeScript and Go. Currently building Grow More Gaze — a SaaS platform for payments and payroll. I maintain open-source tools at @softpulze, explore agent engineering and LLM integrations, and enjoy shipping pragmatic, well-crafted software.',
        location: 'Chittagong, Bangladesh',
        currentWork: 'Grow More Gaze — SaaS platform (payments, payroll)',
        sideProjects: 'Open-source at @softpulze · HovyProject',
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
            new SkillCategoryDTO(category: 'Languages', items: ['PHP', 'TypeScript', 'JavaScript', 'Go', 'Rust', 'Python', 'SQL', 'HTML5', 'CSS3']),
            new SkillCategoryDTO(category: 'Frameworks & Libraries', items: ['Laravel', 'Vue.js', 'Inertia.js', 'Nuxt.js', 'React', 'Next.js', 'Tailwind CSS', 'Livewire', 'Alpine.js']),
            new SkillCategoryDTO(category: 'Tools & Platforms', items: ['Docker', 'MySQL', 'PostgreSQL', 'Redis', 'Nginx', 'Linux', 'Git', 'Composer', 'VS Code', 'MQTT']),
            new SkillCategoryDTO(category: 'DevOps & CI/CD', items: ['GitHub Actions', 'GitHub Container Registry', 'Elasticsearch', 'Graylog', 'Deployer', 'Certbot']),
            new SkillCategoryDTO(category: 'AI & Agent Engineering', items: ['OpenClaw', 'AI Agent CLI', 'Skill Workshop', 'Prompt Engineering', 'MCP', 'LLM API Integration', 'AI-Augmented Dev']),
        ],
        projects: [
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
                role: 'Full-stack Engineer',
                company: 'Grow More Gaze',
                description: 'Building a SaaS platform covering payments and payroll.',
                period: 'Present',
            ),
            new ExperienceDTO(
                role: 'Open-source Maintainer',
                company: '@softpulze',
                description: 'Maintaining Laravel ecosystem packages, starter kits, and developer tooling.',
                period: 'Active',
                url: 'https://github.com/softpulze',
            ),
        ],
    )->toArray(),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
