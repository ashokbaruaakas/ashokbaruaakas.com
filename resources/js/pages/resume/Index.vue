<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Globe, Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import PortfolioBackLink from '@/components/portfolio/PortfolioBackLink.vue';
import ResumeDownloadButton from '@/components/portfolio/ResumeDownloadButton.vue';
import { openSource } from '@/routes';
import type { Portfolio, SeoMetadata } from '@/types/portfolio';

const { portfolio, seo } = defineProps<{
    portfolio: Portfolio;
    seo: SeoMetadata;
}>();

const pageTitle = 'Resume | Senior Full-Stack Engineer';
const shareTitle = `${portfolio.name} — ${pageTitle}`;
const pageDescription =
    'Review Ashok Barua’s resume covering fintech payments, SiPay, HSM and card saving, multi-tenant SaaS ownership, technical skills, and work history.';

const formattedPhone = computed(() =>
    portfolio.phone.replace(/^([+]\d{3})(\d{4})(\d{6})$/, '$1 $2 $3'),
);

const githubUrl = computed(
    () =>
        portfolio.socialLinks.find(({ platform }) => platform === 'GitHub')
            ?.url ?? `https://github.com/${portfolio.githubUsername}`,
);

const websiteLabel = computed(() =>
    portfolio.websiteUrl.replace(/^https?:\/\//, '').replace(/\/$/, ''),
);

const openSourceTitles = computed(() =>
    portfolio.openSourceContributions.map(({ title }) => title).join(', '),
);

function projectLinkLabel(project: Portfolio['projects'][number]): string {
    return project.linkLabel ?? (project.isPublicRepo ? 'GitHub' : 'Live');
}
</script>

<template>
    <Head :title="pageTitle">
        <meta
            head-key="description"
            name="description"
            :content="pageDescription"
        />
        <link head-key="canonical" rel="canonical" :href="seo.canonicalUrl" />
        <meta head-key="og:title" property="og:title" :content="shareTitle" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="pageDescription"
        />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:url" property="og:url" :content="seo.canonicalUrl" />
        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="portfolio.name"
        />
        <meta head-key="og:image" property="og:image" :content="seo.imageUrl" />
        <meta
            head-key="og:image:secure_url"
            property="og:image:secure_url"
            :content="seo.imageUrl"
        />
        <meta
            head-key="og:image:type"
            property="og:image:type"
            content="image/png"
        />
        <meta
            head-key="og:image:width"
            property="og:image:width"
            content="1200"
        />
        <meta
            head-key="og:image:height"
            property="og:image:height"
            content="630"
        />
        <meta
            head-key="og:image:alt"
            property="og:image:alt"
            content="Ashok Barua — Senior Full-Stack Engineer"
        />
        <meta
            head-key="twitter:card"
            name="twitter:card"
            content="summary_large_image"
        />
        <meta
            head-key="twitter:title"
            name="twitter:title"
            :content="shareTitle"
        />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="pageDescription"
        />
        <meta
            head-key="twitter:image"
            name="twitter:image"
            :content="seo.imageUrl"
        />
        <meta
            head-key="twitter:image:alt"
            name="twitter:image:alt"
            content="Ashok Barua — Senior Full-Stack Engineer"
        />
    </Head>

    <div class="px-6 pt-24 pb-16 lg:px-12 print:!px-0 print:!pt-0 print:!pb-0">
        <div class="mx-auto w-full max-w-[210mm]">
            <PortfolioBackLink />

            <div class="resume-page">
                <!-- Header -->
                <div class="resume-header">
                    <h1>{{ portfolio.name }}</h1>
                    <div class="resume-title">
                        {{ portfolio.tagline }}
                    </div>
                    <div class="resume-contact-row">
                        <span class="resume-contact-item">
                            <MapPin
                                class="resume-contact-icon"
                                aria-hidden="true"
                            />
                            <span class="resume-contact-label">Location: </span>
                            {{ portfolio.location }}
                        </span>
                        <span class="resume-contact-item">
                            <Phone
                                class="resume-contact-icon"
                                aria-hidden="true"
                            />
                            <span class="resume-contact-label">Phone: </span>
                            <a :href="`tel:${portfolio.phone}`">{{
                                formattedPhone
                            }}</a>
                        </span>
                        <span class="resume-contact-item">
                            <Mail
                                class="resume-contact-icon"
                                aria-hidden="true"
                            />
                            <span class="resume-contact-label">Email: </span>
                            <a :href="`mailto:${portfolio.email}`">{{
                                portfolio.email
                            }}</a>
                        </span>
                        <span
                            v-if="portfolio.websiteUrl"
                            class="resume-contact-item"
                        >
                            <Globe
                                class="resume-contact-icon"
                                aria-hidden="true"
                            />
                            <span class="resume-contact-label">Website: </span>
                            <a
                                :href="portfolio.websiteUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span class="resume-screen-contact-value">{{
                                    websiteLabel
                                }}</span>
                                <span class="resume-print-contact-value">{{
                                    portfolio.websiteUrl
                                }}</span>
                                <span class="sr-only"
                                    >(opens in a new tab)</span
                                >
                            </a>
                        </span>
                        <span class="resume-contact-item">
                            <svg
                                class="resume-contact-icon"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"
                                />
                            </svg>
                            <span class="resume-contact-label">GitHub: </span>
                            <a
                                :href="githubUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span class="resume-screen-contact-value">{{
                                    githubUrl.replace(/^https?:\/\//, '')
                                }}</span>
                                <span class="resume-print-contact-value">{{
                                    githubUrl
                                }}</span>
                                <span class="sr-only"
                                    >(opens in a new tab)</span
                                >
                            </a>
                        </span>
                    </div>
                </div>

                <!-- Body -->
                <div class="resume-body">
                    <!-- Summary -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">
                            Professional Summary
                        </h2>
                        <div class="resume-summary">
                            {{ portfolio.professionalSummary }}
                        </div>
                    </div>

                    <!-- Skills -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">Technical Skills</h2>
                        <div class="resume-skills-grid">
                            <div
                                v-for="category in portfolio.skills"
                                :key="category.category"
                                class="resume-skill-group"
                            >
                                <h3>{{ category.category }}</h3>
                                <p>{{ category.items.join(', ') }}</p>
                                <p
                                    v-if="
                                        category.category === 'Languages' &&
                                        portfolio.familiarSkills.length
                                    "
                                >
                                    Familiar with:
                                    {{ portfolio.familiarSkills.join(', ') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Experience -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">Experience</h2>
                        <div
                            v-for="entry in portfolio.experience"
                            :key="`${entry.company}-${entry.role}`"
                            class="resume-exp-item"
                        >
                            <div class="resume-exp-header">
                                <div class="resume-role-group">
                                    <h3 class="resume-role">
                                        {{ entry.role }}
                                    </h3>
                                    <span class="resume-sep"> - </span>
                                    <a
                                        v-if="entry.url"
                                        :href="entry.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="resume-company"
                                    >
                                        {{ entry.company }}
                                        <ArrowUpRight
                                            class="resume-external-icon size-3"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                    <span v-else class="resume-company">
                                        {{ entry.company }}
                                    </span>
                                </div>
                                <span class="resume-date">{{
                                    entry.period
                                }}</span>
                            </div>
                            <ul class="resume-exp-bullets">
                                <li>{{ entry.description }}</li>
                                <li
                                    v-for="highlight in entry.highlights"
                                    :key="highlight"
                                >
                                    {{ highlight }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Projects -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">
                            Additional Projects
                        </h2>
                        <div class="resume-section-subtitle">
                            Supporting projects
                        </div>

                        <div
                            v-for="project in portfolio.projects.filter(
                                ({ tier }) => tier === 2,
                            )"
                            :key="project.name"
                            class="resume-proj-item"
                        >
                            <div class="resume-proj-header">
                                <h3 class="resume-proj-name">
                                    {{ project.name }}
                                </h3>
                                <span class="resume-proj-links">
                                    <a
                                        v-if="project.demoUrl"
                                        :href="project.demoUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ projectLinkLabel(project) }}
                                        <ArrowUpRight
                                            class="resume-external-icon size-3"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                    <a
                                        v-if="project.secondaryUrl"
                                        :href="project.secondaryUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ project.secondaryLabel }}
                                        <ArrowUpRight
                                            class="resume-external-icon size-3"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </a>
                                </span>
                            </div>
                            <div class="resume-proj-desc">
                                {{ project.description }}
                                <template v-if="project.metric">
                                    {{ project.metric }}.</template
                                >
                            </div>
                            <div class="resume-proj-tech">
                                <span
                                    v-for="technology in project.technologies"
                                    :key="technology"
                                >
                                    {{ technology }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Open Source -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">Open Source</h2>
                        <div class="resume-summary">
                            Open-source projects and contributions include
                            {{ openSourceTitles }}. View the full contribution
                            timeline at
                            <Link :href="openSource.url()">
                                {{ websiteLabel }}/open-source </Link
                            >.
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">Education</h2>
                        <div class="resume-edu-grid">
                            <div
                                v-for="item in portfolio.education"
                                :key="`${item.degree}-${item.school}`"
                                class="resume-edu-item"
                            >
                                <div class="resume-edu-copy">
                                    <h3 class="resume-degree">
                                        {{ item.degree }}
                                    </h3>
                                    <span class="resume-school">
                                        — {{ item.school }}</span
                                    >
                                </div>
                                <span class="resume-date">{{
                                    item.period
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <hr />

                    <!-- Languages -->
                    <div class="resume-section">
                        <h2 class="resume-section-title">Languages</h2>
                        <div class="resume-lang-list">
                            <span
                                v-for="language in portfolio.languages"
                                :key="language.name"
                            >
                                {{ language.name }}
                                <span class="resume-lang-level"
                                    >({{ language.level }})</span
                                >
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <ResumeDownloadButton class="mt-6" />
        </div>
    </div>
</template>

<style>
.resume-page {
    max-width: 210mm;
    margin: 0 auto;
    background: #ffffff;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(0, 0, 0, 0.04);
    border-radius: 8px;
    overflow: hidden;
}

.resume-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #f8fafc;
    padding: 28px 36px 22px;
}

.resume-header h1 {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 24pt;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin-bottom: 3px;
}

.resume-title {
    font-size: 10.5pt;
    color: #94a3b8;
    margin-bottom: 10px;
}

.resume-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 12px;
    font-size: 8pt;
    color: #cbd5e1;
}

.resume-contact-row a {
    color: #34d399;
    text-decoration: none;
}

.resume-contact-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.resume-contact-label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.resume-print-contact-value {
    display: none;
}

.resume-contact-icon {
    width: 11px;
    height: 11px;
    flex-shrink: 0;
}

.resume-body {
    padding: 24px 36px 28px;
}

.resume-section {
    margin-bottom: 18px;
}

.resume-section-title {
    font-size: 10.5pt;
    font-weight: 700;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-bottom: 3px;
    border-bottom: 2px solid #10b981;
    margin-top: 0;
    margin-bottom: 8px;
}

.resume-section-subtitle {
    font-size: 8.5pt;
    color: #64748b;
    margin-top: -4px;
    margin-bottom: 8px;
}

.resume-summary {
    font-size: 9.5pt;
    color: #475569;
    line-height: 1.6;
}

.resume-skills-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.resume-skill-group {
    flex: 1;
    min-width: 140px;
}

.resume-skill-group h3 {
    font-size: 8pt;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.resume-skill-group p {
    font-size: 9pt;
    color: #1e293b;
    line-height: 1.5;
}

.resume-exp-item {
    margin-bottom: 12px;
}

.resume-exp-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    margin-bottom: 1px;
}

.resume-role {
    font-size: 10pt;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.resume-company {
    font-size: 9pt;
    font-weight: 500;
    color: #047857;
}

.resume-role-group {
    display: inline-flex;
    align-items: baseline;
}

.resume-sep {
    font-size: 9pt;
    color: #64748b;
    padding: 0 4px;
}

.resume-date {
    font-size: 8pt;
    color: #64748b;
    white-space: nowrap;
}

.resume-exp-bullets {
    list-style: none;
    padding-left: 0;
}

.resume-exp-bullets li {
    font-size: 8.5pt;
    color: #475569;
    padding-left: 14px;
    position: relative;
    margin-bottom: 1px;
}

.resume-exp-bullets li::before {
    content: '\25B8';
    position: absolute;
    left: 0;
    color: #047857;
}

.resume-external-icon {
    display: inline-block;
    margin-left: 2px;
    vertical-align: -0.125em;
    flex-shrink: 0;
}

.resume-proj-item {
    margin-bottom: 9px;
}

.resume-proj-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
}

.resume-proj-name {
    font-size: 9.5pt;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.resume-proj-links {
    font-size: 8pt;
}

.resume-proj-links a {
    color: #047857;
    text-decoration: none;
}

.resume-proj-desc {
    font-size: 8.5pt;
    color: #475569;
    margin-top: 1px;
}

.resume-proj-tech {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 2px;
}

.resume-proj-tech span {
    font-size: 7pt;
    background: #d1fae5;
    color: #065f46;
    padding: 1px 7px;
    border-radius: 3px;
    font-weight: 500;
}

.resume-edu-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.resume-edu-item {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
}

.resume-degree {
    display: inline;
    font-size: 9pt;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.resume-school {
    font-size: 8.5pt;
    color: #475569;
}

.resume-lang-list {
    display: flex;
    gap: 20px;
}

.resume-lang-list span {
    font-size: 9pt;
    color: #475569;
}

.resume-lang-level {
    color: #64748b;
}

.resume-body hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 12px 0;
}

@media print {
    @page {
        size: A4;
        margin: 12mm;
    }

    html,
    body {
        background: #fff !important;
        color: #000 !important;
        font-family: Arial, Helvetica, sans-serif !important;
    }

    body * {
        color: #000 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        background-color: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        text-shadow: none !important;
    }

    .resume-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        overflow: visible;
        background-color: #fff !important;
        border: 0;
        border-radius: 0;
    }

    .resume-header {
        padding: 0 0 8mm;
        background-color: #fff !important;
    }

    .resume-header h1,
    .resume-title,
    .resume-contact-row,
    .resume-contact-row a {
        color: #000 !important;
    }

    .resume-contact-row {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }

    .resume-contact-item {
        display: inline-flex;
        align-items: baseline;
    }

    .resume-contact-label {
        position: static;
        width: auto;
        height: auto;
        margin: 0;
        overflow: visible;
        clip: auto;
        white-space: normal;
    }

    .resume-contact-icon,
    .resume-screen-contact-value,
    .resume-external-icon {
        display: none !important;
    }

    .resume-print-contact-value {
        display: inline !important;
    }

    .resume-body {
        padding: 0;
    }

    .resume-skills-grid {
        display: block;
    }

    .resume-skill-group {
        display: block;
        width: 100%;
        margin-bottom: 4px;
    }

    .resume-exp-header,
    .resume-proj-header,
    .resume-edu-item {
        display: block;
    }

    .resume-lang-list,
    .resume-lang-list > span {
        display: block;
    }

    .resume-exp-item,
    .resume-proj-item,
    .resume-edu-item,
    .resume-section-title {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .resume-section-title {
        break-after: avoid;
        page-break-after: avoid;
    }

    .resume-page a {
        text-decoration: none !important;
    }

    .resume-exp-bullets li::before {
        color: #000 !important;
    }
}

@media screen and (max-width: 600px) {
    .resume-header {
        padding: 16px 14px;
    }

    .resume-body {
        padding: 14px;
    }

    .resume-header h1 {
        font-size: 20pt;
    }

    .resume-skills-grid {
        flex-direction: column;
    }

    .resume-exp-header {
        flex-direction: column;
    }

    .resume-contact-row {
        flex-direction: column;
        gap: 3px;
    }
}
</style>
