<script setup lang="ts">
import { Globe, Mail, MapPin, Phone } from '@lucide/vue'
import { computed } from 'vue'
import type { Portfolio, Project } from '@/types/portfolio'

const { portfolio } = defineProps<{
    portfolio: Portfolio
}>()

const SIDEBAR_WIDTH_PT = 175
const AVERAGE_GLYPH_WIDTH_EM = 0.58
const MAX_NAME_SIZE_PT = 26

const nameSize = computed(() => `${Math.min(MAX_NAME_SIZE_PT, SIDEBAR_WIDTH_PT / (portfolio.name.length * AVERAGE_GLYPH_WIDTH_EM)).toFixed(1)}pt`)

const taglineParts = computed(() => {
    const [title, focus] = portfolio.tagline.split(' — ')

    return { title, focus }
})

const displayUrl = (url: string) => url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '')

const githubUrl = computed(
    () =>
        portfolio.socialLinks.find(({ platform }) => platform === 'GitHub')?.url ??
        (portfolio.githubUsername ? `https://github.com/${portfolio.githubUsername}` : null),
)

const linkedInUrl = computed(() => portfolio.socialLinks.find(({ platform }) => platform === 'LinkedIn')?.url ?? null)

const projectUrl = (project: Project): string | null => project.demoUrl ?? project.links.find(({ isPrimary }) => isPrimary)?.url ?? null

const mergedContributions = computed(() => portfolio.openSourceContributions.filter(({ type }) => ['Pull Request', 'Contributions'].includes(type)))

const contributionsSentence = computed(() => {
    const names = mergedContributions.value.map(({ organization }) => organization)

    return names.length > 1 ? `${names.slice(0, -1).join(', ')} and ${names.at(-1)}` : (names[0] ?? '')
})
</script>

<template>
    <div class="resume-sidebar">
        <aside class="resume-sidebar-aside">
            <div class="resume-sidebar-identity">
                <h1 :style="{ fontSize: nameSize }">{{ portfolio.name }}</h1>
                <p class="resume-sidebar-title">{{ taglineParts.title }}</p>
                <p v-if="taglineParts.focus" class="resume-sidebar-focus">{{ taglineParts.focus }}</p>
            </div>

            <section class="resume-sidebar-section">
                <h2><span>Contact</span></h2>
                <ul class="resume-sidebar-contact">
                    <li v-if="linkedInUrl">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452z"
                            />
                        </svg>
                        <a :href="linkedInUrl" target="_blank" rel="noopener noreferrer">{{ displayUrl(linkedInUrl) }}</a>
                    </li>
                    <li v-if="githubUrl">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"
                            />
                        </svg>
                        <a :href="githubUrl" target="_blank" rel="noopener noreferrer">{{ displayUrl(githubUrl) }}</a>
                    </li>
                    <li v-if="portfolio.websiteUrl">
                        <Globe aria-hidden="true" />
                        <a :href="portfolio.websiteUrl" target="_blank" rel="noopener noreferrer">{{ displayUrl(portfolio.websiteUrl) }}</a>
                    </li>
                    <li v-if="portfolio.phone">
                        <Phone aria-hidden="true" />
                        <a :href="`tel:${portfolio.phone}`">{{ portfolio.phoneDisplay }}</a>
                    </li>
                    <li v-if="portfolio.email">
                        <Mail aria-hidden="true" />
                        <a :href="`mailto:${portfolio.email}`">{{ portfolio.email }}</a>
                    </li>
                    <li v-if="portfolio.location">
                        <MapPin aria-hidden="true" />
                        <span
                            >{{ portfolio.location }}<template v-if="portfolio.timezoneLabel"> ({{ portfolio.timezoneLabel }})</template
                            ><template v-if="portfolio.availability"><br />{{ portfolio.availability }}</template></span
                        >
                    </li>
                </ul>
            </section>

            <section v-if="portfolio.professionalSummary" class="resume-sidebar-section">
                <h2><span>About Me</span></h2>
                <p class="resume-sidebar-text">{{ portfolio.professionalSummary }}</p>
            </section>

            <section v-if="portfolio.skills.length" class="resume-sidebar-section">
                <h2><span>Skills</span></h2>
                <div v-for="category in portfolio.skills" :key="category.category" class="resume-sidebar-skill">
                    <h3>{{ category.category }}</h3>
                    <p>{{ category.items.join(', ') }}</p>
                </div>
                <div v-if="portfolio.familiarSkills.length" class="resume-sidebar-skill">
                    <h3>Familiar With</h3>
                    <p>{{ portfolio.familiarSkills.join(', ') }}</p>
                </div>
            </section>

            <section v-if="portfolio.languages.length" class="resume-sidebar-section">
                <h2><span>Language</span></h2>
                <ul class="resume-sidebar-bullets">
                    <li v-for="language in portfolio.languages" :key="language.name">{{ language.name }} ({{ language.level }})</li>
                </ul>
            </section>
        </aside>

        <main class="resume-sidebar-main">
            <section v-if="portfolio.experience.length" class="resume-sidebar-section">
                <h2><span>Experience</span></h2>
                <div v-for="entry in portfolio.experience" :key="`${entry.company}-${entry.role}`" class="resume-sidebar-entry">
                    <div class="resume-sidebar-entry-head">
                        <h3>{{ entry.role }}</h3>
                        <span>{{ entry.period }}</span>
                    </div>
                    <p class="resume-sidebar-meta">
                        <a v-if="entry.url" :href="entry.url" target="_blank" rel="noopener noreferrer">{{ entry.company }}</a
                        ><template v-else>{{ entry.company }}</template
                        ><template v-if="entry.description"> · {{ entry.description }}</template>
                    </p>
                    <ul v-if="entry.highlights.length" class="resume-sidebar-bullets">
                        <li v-for="highlight in entry.highlights" :key="highlight">{{ highlight }}</li>
                    </ul>
                </div>
            </section>

            <section v-if="portfolio.projects.length || mergedContributions.length" class="resume-sidebar-section">
                <h2><span>Projects</span></h2>
                <div v-for="project in portfolio.projects" :key="project.name" class="resume-sidebar-entry">
                    <p class="resume-sidebar-project">
                        <strong>{{ project.name }}</strong
                        ><template v-if="projectUrl(project)">
                            –
                            <a :href="projectUrl(project)!" target="_blank" rel="noopener noreferrer">{{
                                displayUrl(projectUrl(project)!)
                            }}</a></template
                        >
                    </p>
                    <ul class="resume-sidebar-bullets">
                        <li>{{ project.metric ? `${project.description} ${project.metric}.` : project.description }}</li>
                    </ul>
                </div>
                <ul v-if="mergedContributions.length" class="resume-sidebar-bullets">
                    <li>Merged contributions to {{ contributionsSentence }}</li>
                </ul>
            </section>

            <section v-if="portfolio.education.length" class="resume-sidebar-section">
                <h2><span>Education</span></h2>
                <div v-for="item in portfolio.education" :key="`${item.degree}-${item.school}`" class="resume-sidebar-entry">
                    <div class="resume-sidebar-entry-head">
                        <h3>{{ item.degree }}</h3>
                        <span>{{ item.period }}</span>
                    </div>
                    <p class="resume-sidebar-meta">{{ item.school }}</p>
                </div>
            </section>
        </main>
    </div>
</template>

<style>
.resume-sidebar {
    display: grid;
    grid-template-columns: 62mm 1fr;
    column-gap: 9mm;
    font-family: var(--resume-font, Arial, Helvetica, sans-serif);
    font-size: 8.5pt;
    line-height: 1.4;
    color: #333;
}

.resume-sidebar a {
    color: inherit;
    text-decoration: none;
}

.resume-sidebar h1,
.resume-sidebar h2,
.resume-sidebar h3,
.resume-sidebar p,
.resume-sidebar ul {
    margin: 0;
}

.resume-sidebar-identity {
    text-align: center;
    margin-bottom: 5mm;
}

.resume-sidebar-identity h1 {
    font-family: var(--resume-name-font, inherit);
    font-weight: 700;
    line-height: 1.1;
    white-space: nowrap;
    color: #222;
}

.resume-sidebar-title {
    margin-top: 2px;
    font-size: 11pt;
    color: #444;
}

.resume-sidebar-focus {
    font-size: 7.5pt;
    color: #999;
}

.resume-sidebar-section {
    margin-bottom: 4mm;
}

.resume-sidebar-section h2 {
    display: flex;
    align-items: center;
    gap: 4mm;
    margin-bottom: 2mm;
    font-size: 14pt;
    font-weight: 600;
    line-height: 1.2;
    color: #333;
    break-after: avoid;
}

.resume-sidebar-section h2::after {
    content: '';
    margin-left: auto;
    width: 12mm;
    height: 3px;
    background: var(--resume-accent, #c8c8c8);
}

.resume-sidebar-contact {
    display: grid;
    gap: 3px;
    list-style: none;
    padding: 0;
    overflow-wrap: anywhere;
}

.resume-sidebar-contact li {
    display: flex;
    align-items: flex-start;
    gap: 2.5mm;
}

.resume-sidebar-contact svg {
    flex-shrink: 0;
    width: 3.2mm;
    height: 3.2mm;
    margin-top: 0.5mm;
}

.resume-sidebar-text {
    line-height: 1.5;
}

.resume-sidebar-skill {
    margin-bottom: 2mm;
    break-inside: avoid;
}

.resume-sidebar-skill h3 {
    font-size: 8.5pt;
    font-weight: 600;
    color: #222;
}

.resume-sidebar-entry {
    margin-bottom: 2.5mm;
    break-inside: avoid;
}

.resume-sidebar-entry-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 3mm;
}

.resume-sidebar-entry-head h3 {
    flex: 1 1 0;
    min-width: 50%;
    font-size: 9.5pt;
    font-weight: 600;
    color: #222;
}

.resume-sidebar-entry-head span {
    flex: 0 1 auto;
    font-size: 8.5pt;
    text-align: right;
}

.resume-sidebar-meta {
    margin-bottom: 1mm;
    font-style: italic;
    color: #777;
}

.resume-sidebar-project strong {
    font-weight: 600;
    color: #222;
}

.resume-sidebar-bullets {
    padding-left: 4mm;
    list-style: disc;
}

.resume-sidebar-bullets li {
    margin-bottom: 0.5mm;
}
</style>
