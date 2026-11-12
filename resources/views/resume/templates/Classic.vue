<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { openSource } from '@/routes'
import type { Portfolio, Project } from '@/types/portfolio'

const { portfolio } = defineProps<{
    portfolio: Portfolio
}>()

const taglineParts = computed(() => {
    const [title, focus] = portfolio.tagline.split(' — ')

    return { title, focus }
})

const githubUrl = computed(
    () =>
        portfolio.socialLinks.find(({ platform }) => platform === 'GitHub')?.url ??
        (portfolio.githubUsername ? `https://github.com/${portfolio.githubUsername}` : null),
)

const websiteLabel = computed(() => portfolio.websiteUrl.replace(/^https?:\/\//, '').replace(/\/$/, ''))

const linkedInUrl = computed(() => portfolio.socialLinks.find(({ platform }) => platform === 'LinkedIn')?.url)

function primaryLinkUrls(project: Project): string[] {
    return [...(project.demoUrl ? [project.demoUrl] : []), ...project.links.filter(({ isPrimary }) => isPrimary).map(({ url }) => url)]
}

const resumeProjects = computed(() => portfolio.projects)

const mergedContributions = computed(() => portfolio.openSourceContributions.filter(({ type }) => ['Pull Request', 'Contributions'].includes(type)))
</script>

<template>
    <div class="resume-classic">
        <div class="resume-header">
            <h1>{{ portfolio.name }}</h1>
            <div class="resume-title">
                {{ taglineParts.title }}<template v-if="taglineParts.focus"> — {{ taglineParts.focus }}</template>
            </div>
            <div class="resume-contact-row">
                <span v-if="portfolio.location">
                    Location: {{ portfolio.location }}<template v-if="portfolio.timezoneLabel"> ({{ portfolio.timezoneLabel }})</template
                    ><template v-if="portfolio.availability"> · {{ portfolio.availability }}</template>
                </span>
                <span v-if="portfolio.phone">
                    Phone: <a :href="`tel:${portfolio.phone}`">{{ portfolio.phoneDisplay }}</a>
                </span>
                <span v-if="portfolio.email">
                    Email: <a :href="`mailto:${portfolio.email}`">{{ portfolio.email }}</a>
                </span>
                <span v-if="portfolio.websiteUrl">
                    Website:
                    <a :href="portfolio.websiteUrl" target="_blank" rel="noopener noreferrer"
                        >{{ portfolio.websiteUrl }}<span class="sr-only"> (opens in a new tab)</span></a
                    >
                </span>
                <span v-if="githubUrl">
                    GitHub:
                    <a :href="githubUrl" target="_blank" rel="noopener noreferrer"
                        >{{ githubUrl }}<span class="sr-only"> (opens in a new tab)</span></a
                    >
                </span>
                <span v-if="linkedInUrl">
                    LinkedIn:
                    <a :href="linkedInUrl" target="_blank" rel="noopener noreferrer"
                        >{{ linkedInUrl }}<span class="sr-only"> (opens in a new tab)</span></a
                    >
                </span>
            </div>
        </div>

        <div v-if="portfolio.professionalSummary" class="resume-section">
            <h2 class="resume-section-title">Professional Summary</h2>
            <p class="resume-summary">{{ portfolio.professionalSummary }}</p>
        </div>

        <div v-if="portfolio.skills.length" class="resume-section">
            <h2 class="resume-section-title">Technical Skills</h2>
            <div v-for="category in portfolio.skills" :key="category.category" class="resume-skill-group">
                <h3>{{ category.category }}<span class="resume-inline-sep">: </span></h3>
                <p>{{ category.items.join(', ') }}</p>
                <p v-if="category.category === 'Languages' && portfolio.familiarSkills.length">
                    <span class="resume-inline-sep">; </span>Familiar with: {{ portfolio.familiarSkills.join(', ') }}
                </p>
            </div>
        </div>

        <div v-if="portfolio.experience.length" class="resume-section">
            <h2 class="resume-section-title">Experience</h2>
            <div v-for="entry in portfolio.experience" :key="`${entry.company}-${entry.role}`" class="resume-item resume-exp-item">
                <div>
                    <h3 class="resume-role">{{ entry.role }}</h3>
                    <span class="resume-sep"> — </span>
                    <a v-if="entry.url" :href="entry.url" target="_blank" rel="noopener noreferrer" class="resume-company"
                        >{{ entry.company }}<span class="sr-only"> (opens in a new tab)</span></a
                    >
                    <span v-else class="resume-company">{{ entry.company }}</span>
                    <span class="resume-date"> | {{ entry.period }}</span>
                </div>
                <p v-if="entry.description" class="resume-exp-context">{{ entry.description }}</p>
                <ul class="resume-exp-bullets">
                    <li v-for="highlight in entry.highlights" :key="highlight">{{ highlight }}</li>
                </ul>
            </div>
        </div>

        <div v-if="resumeProjects.length || mergedContributions.length" class="resume-section">
            <h2 class="resume-section-title">Additional Projects</h2>
            <div v-for="project in resumeProjects" :key="project.name" class="resume-item resume-proj-item">
                <div>
                    <h3 class="resume-proj-name">{{ project.name }}</h3>
                    <a
                        v-for="url in primaryLinkUrls(project)"
                        :key="url"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="resume-proj-link"
                        >{{ url }}</a
                    >
                </div>
                <p class="resume-proj-desc">
                    {{ project.metric ? `${project.description} ${project.metric}.` : project.description }}
                </p>
            </div>
            <p v-if="mergedContributions.length" class="resume-proj-desc">
                Merged contributions to
                <template v-for="(contribution, index) in mergedContributions" :key="contribution.url"
                    >{{ index === 0 ? '' : index === mergedContributions.length - 1 ? ' and ' : ', '
                    }}<a :href="contribution.url" target="_blank" rel="noopener noreferrer"
                        >{{ contribution.organization }}<span class="sr-only"> (opens in a new tab)</span></a
                    ></template
                >. Full timeline at <Link :href="openSource.url()">{{ websiteLabel }}/open-source</Link>.
            </p>
        </div>

        <div v-if="portfolio.education.length" class="resume-section">
            <h2 class="resume-section-title">Education</h2>
            <div v-for="item in portfolio.education" :key="`${item.degree}-${item.school}`" class="resume-item resume-edu-item">
                <h3 class="resume-degree">{{ item.degree }}</h3>
                <span class="resume-school"> — {{ item.school }}</span>
                <div class="resume-date"><span class="resume-inline-sep"> | </span>{{ item.period }}</div>
            </div>
        </div>

        <div v-if="portfolio.languages.length" class="resume-section">
            <h2 class="resume-section-title">Languages</h2>
            <div class="resume-lang-list">
                <span v-for="language in portfolio.languages" :key="language.name">{{ language.name }} ({{ language.level }})</span>
            </div>
        </div>
    </div>
</template>

<style>
.resume-classic {
    font-family: var(--resume-font, Arial, Helvetica, sans-serif);
    color: #000;
}

.resume-classic a {
    color: inherit;
    text-decoration: none;
}

.resume-classic h1,
.resume-classic h2,
.resume-classic h3,
.resume-classic p,
.resume-classic ul {
    margin: 0;
}

.resume-classic .resume-header {
    padding-bottom: 8mm;
}

.resume-classic .resume-header h1 {
    font-family: var(--resume-name-font, inherit);
    font-size: 24pt;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin-bottom: 3px;
}

.resume-classic .resume-title {
    font-size: 10.5pt;
    margin-bottom: 10px;
}

.resume-classic .resume-contact-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    font-size: 8pt;
    overflow-wrap: anywhere;
}

.resume-classic .resume-inline-sep {
    display: none;
}

.resume-classic .resume-section {
    margin-bottom: 14px;
}

.resume-classic .resume-section-title {
    font-size: 10.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-bottom: 3px;
    border-bottom: 2px solid var(--resume-accent, #10b981);
    margin-bottom: 8px;
    break-inside: avoid;
    break-after: avoid;
}

.resume-classic .resume-summary {
    font-size: 9.5pt;
    line-height: 1.6;
}

.resume-classic .resume-skill-group {
    margin-bottom: 4px;
}

.resume-classic .resume-skill-group h3 {
    font-size: 8pt;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.resume-classic .resume-skill-group p {
    font-size: 9pt;
    line-height: 1.5;
}

.resume-classic .resume-item {
    break-inside: avoid;
}

.resume-classic .resume-exp-item {
    margin-bottom: 12px;
}

.resume-classic .resume-role {
    display: inline;
    font-size: 10pt;
    font-weight: 600;
}

.resume-classic .resume-sep {
    font-size: 9pt;
    padding: 0 4px;
}

.resume-classic .resume-company {
    font-size: 9pt;
    font-weight: 500;
}

.resume-classic .resume-date {
    font-size: 8pt;
    white-space: nowrap;
}

.resume-classic .resume-exp-context {
    font-size: 8.5pt;
    font-style: italic;
    margin: 1px 0 2px;
}

.resume-classic .resume-exp-bullets {
    list-style: none;
    padding-left: 0;
}

.resume-classic .resume-exp-bullets li {
    font-size: 8.5pt;
    padding-left: 14px;
    position: relative;
    margin-bottom: 1px;
}

.resume-classic .resume-exp-bullets li::before {
    content: '\25B8';
    position: absolute;
    left: 0;
}

.resume-classic .resume-proj-item {
    margin-bottom: 9px;
}

.resume-classic .resume-proj-name {
    display: inline;
    font-size: 9.5pt;
    font-weight: 600;
}

.resume-classic .resume-proj-link {
    font-size: 8pt;
    margin-left: 8px;
    overflow-wrap: anywhere;
}

.resume-classic .resume-proj-desc {
    font-size: 8.5pt;
    margin-top: 1px;
}

.resume-classic .resume-edu-item {
    margin-bottom: 6px;
}

.resume-classic .resume-degree {
    display: inline;
    font-size: 9pt;
    font-weight: 600;
}

.resume-classic .resume-school {
    font-size: 8.5pt;
}

.resume-classic .resume-lang-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 20px;
    font-size: 9pt;
}
</style>
