<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ArrowUpRight, Download, Globe, Mail, MapPin, Phone } from '@lucide/vue'
import { computed, h } from 'vue'
import PortfolioBackLink from '@/components/portfolio/PortfolioBackLink.vue'
import { buildPersonSchema, serializeStructuredData } from '@/lib/structuredData'
import { openSource } from '@/routes'
import { pdf } from '@/routes/resume'
import type { Portfolio, Project, SeoMetadata } from '@/types/portfolio'

const { portfolio, seo } = defineProps<{
    portfolio: Portfolio
    seo: SeoMetadata
}>()

const page = usePage()

const pageContent = portfolio.pages.resume
const pageTitle = pageContent.title
const shareTitle = `${portfolio.name} — ${pageTitle}`
const pageDescription = pageContent.description

const taglineParts = computed(() => {
    const [title, focus] = portfolio.tagline.split(' — ')

    return { title, focus }
})

const githubUrl = computed(
    () =>
        portfolio.socialLinks.find(({ platform }) => platform === 'GitHub')?.url ??
        (portfolio.githubUsername ? `https://github.com/${portfolio.githubUsername}` : null),
)

const linkedInUrl = computed(() => portfolio.socialLinks.find(({ platform }) => platform === 'LinkedIn')?.url ?? null)

const displayUrl = (url: string) => url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '')

const contactLinks = computed(() =>
    [
        portfolio.email ? { icon: Mail, label: portfolio.email, href: `mailto:${portfolio.email}`, external: false } : null,
        portfolio.phone ? { icon: Phone, label: portfolio.phoneDisplay, href: `tel:${portfolio.phone}`, external: false } : null,
        portfolio.websiteUrl ? { icon: Globe, label: displayUrl(portfolio.websiteUrl), href: portfolio.websiteUrl, external: true } : null,
        linkedInUrl.value ? { icon: ArrowUpRight, label: 'LinkedIn', href: linkedInUrl.value, external: true } : null,
        githubUrl.value ? { icon: ArrowUpRight, label: 'GitHub', href: githubUrl.value, external: true } : null,
    ].filter((link) => link !== null),
)

const projectUrl = (project: Project): string | null => project.demoUrl ?? project.links.find(({ isPrimary }) => isPrimary)?.url ?? null

const mergedContributions = computed(() => portfolio.openSourceContributions.filter(({ type }) => ['Pull Request', 'Contributions'].includes(type)))

const structuredData = computed(() =>
    serializeStructuredData([
        {
            '@type': 'ProfilePage',
            '@id': `${seo.canonicalUrl}#profile`,
            url: seo.canonicalUrl,
            name: shareTitle,
            mainEntity: { '@id': `${seo.homeUrl}#person` },
        },
        buildPersonSchema(portfolio, seo),
    ]),
)

const structuredDataTag = () =>
    h(
        'script',
        {
            'head-key': 'structured-data',
            type: 'application/ld+json',
        },
        structuredData.value,
    )
</script>

<template>
    <Head :title="pageTitle">
        <meta v-if="pageDescription" head-key="description" name="description" :content="pageDescription" />
        <link head-key="canonical" rel="canonical" :href="seo.canonicalUrl" />
        <meta head-key="og:title" property="og:title" :content="shareTitle" />
        <meta v-if="pageDescription" head-key="og:description" property="og:description" :content="pageDescription" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:url" property="og:url" :content="seo.canonicalUrl" />
        <meta head-key="og:site_name" property="og:site_name" :content="portfolio.name" />
        <meta v-if="seo.imageUrl" head-key="og:image" property="og:image" :content="seo.imageUrl" />
        <meta v-if="seo.imageUrl" head-key="og:image:secure_url" property="og:image:secure_url" :content="seo.imageUrl" />
        <meta v-if="seo.imageUrl" head-key="og:image:type" property="og:image:type" content="image/png" />
        <meta v-if="seo.imageUrl" head-key="og:image:width" property="og:image:width" content="1200" />
        <meta v-if="seo.imageUrl" head-key="og:image:height" property="og:image:height" content="630" />
        <meta v-if="seo.imageUrl" head-key="og:image:alt" property="og:image:alt" :content="pageContent.imageAlt" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="shareTitle" />
        <meta v-if="pageDescription" head-key="twitter:description" name="twitter:description" :content="pageDescription" />
        <meta v-if="seo.imageUrl" head-key="twitter:image" name="twitter:image" :content="seo.imageUrl" />
        <meta v-if="seo.imageUrl" head-key="twitter:image:alt" name="twitter:image:alt" :content="pageContent.imageAlt" />
        <component :is="structuredDataTag" />
    </Head>

    <div class="px-4 pt-24 pb-16 sm:px-6 lg:px-12">
        <div class="mx-auto w-full max-w-4xl">
            <PortfolioBackLink />

            <header class="flex flex-col gap-6 border-b border-border pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div class="grid gap-2">
                    <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">{{ portfolio.name }}</h1>
                    <p class="text-lg text-foreground/90">
                        {{ taglineParts.title }}
                        <span v-if="taglineParts.focus" class="block text-base text-muted-foreground">{{ taglineParts.focus }}</span>
                    </p>
                    <p
                        v-if="portfolio.location || portfolio.availability"
                        class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground"
                    >
                        <span v-if="portfolio.location" class="inline-flex items-center gap-1.5">
                            <MapPin class="size-4" aria-hidden="true" />
                            {{ portfolio.location }}<template v-if="portfolio.timezoneLabel"> ({{ portfolio.timezoneLabel }})</template>
                        </span>
                        <span v-if="portfolio.availability" class="text-emerald-700 dark:text-emerald-400">{{ portfolio.availability }}</span>
                    </p>
                </div>
                <a
                    v-if="page.props.hasResumePdf"
                    :href="pdf.url()"
                    download
                    class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-foreground px-5 py-2.5 text-sm font-medium text-background transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:self-auto"
                >
                    <Download class="size-4" aria-hidden="true" />
                    Download PDF
                </a>
            </header>

            <ul v-if="contactLinks.length" class="flex flex-wrap gap-x-5 gap-y-2 border-b border-border py-4 text-sm">
                <li v-for="link in contactLinks" :key="link.href">
                    <a
                        :href="link.href"
                        :target="link.external ? '_blank' : undefined"
                        :rel="link.external ? 'noopener noreferrer' : undefined"
                        class="inline-flex items-center gap-1.5 text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <component :is="link.icon" class="size-4" aria-hidden="true" />
                        {{ link.label }}<span v-if="link.external" class="sr-only"> (opens in a new tab)</span>
                    </a>
                </li>
            </ul>

            <section v-if="portfolio.professionalSummary" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Summary</h2>
                <p class="leading-relaxed text-foreground/90">{{ portfolio.professionalSummary }}</p>
            </section>

            <section v-if="portfolio.experience.length" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Experience</h2>
                <ol class="grid gap-8">
                    <li
                        v-for="entry in portfolio.experience"
                        :key="`${entry.company}-${entry.role}`"
                        class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:gap-6"
                    >
                        <p class="text-sm text-muted-foreground tabular-nums sm:pt-0.5">{{ entry.period }}</p>
                        <div>
                            <h3 class="font-medium">{{ entry.role }}</h3>
                            <p class="text-sm">
                                <a
                                    v-if="entry.url"
                                    :href="entry.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    {{ entry.company }}
                                    <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                    <span class="sr-only">(opens in a new tab)</span>
                                </a>
                                <span v-else class="text-muted-foreground">{{ entry.company }}</span>
                            </p>
                            <p v-if="entry.description" class="mt-1 text-sm text-muted-foreground">{{ entry.description }}</p>
                            <ul v-if="entry.highlights.length" class="mt-3 grid gap-1.5">
                                <li v-for="(highlight, index) in entry.highlights" :key="index" class="relative pl-4 text-sm text-foreground/85">
                                    <span class="absolute top-2 left-0 size-1.5 rounded-full bg-emerald-500" aria-hidden="true" />
                                    {{ highlight }}
                                </li>
                            </ul>
                        </div>
                    </li>
                </ol>
            </section>

            <section v-if="portfolio.projects.length || mergedContributions.length" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Projects and Open Source</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="project in portfolio.projects" :key="project.name" class="rounded-xl border border-border p-4">
                        <h3 class="font-medium">
                            <a
                                v-if="projectUrl(project)"
                                :href="projectUrl(project)!"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 hover:text-emerald-700 dark:hover:text-emerald-400"
                            >
                                {{ project.name }}
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                <span class="sr-only">(opens in a new tab)</span>
                            </a>
                            <template v-else>{{ project.name }}</template>
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ project.metric ? `${project.description} ${project.metric}.` : project.description }}
                        </p>
                    </article>
                </div>
                <p v-if="mergedContributions.length" class="mt-4 text-sm text-muted-foreground">
                    Merged contributions to
                    <template v-for="(contribution, index) in mergedContributions" :key="contribution.url"
                        >{{ index === 0 ? '' : index === mergedContributions.length - 1 ? ' and ' : ', '
                        }}<a
                            :href="contribution.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-foreground underline-offset-4 hover:underline"
                            >{{ contribution.organization }}<span class="sr-only"> (opens in a new tab)</span></a
                        ></template
                    >.
                    <Link v-if="portfolio.hasOpenSource" :href="openSource.url()" class="text-foreground underline-offset-4 hover:underline"
                        >See all</Link
                    >
                </p>
            </section>

            <section v-if="portfolio.skills.length" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Skills</h2>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div v-for="category in portfolio.skills" :key="category.category">
                        <dt class="text-sm font-medium">{{ category.category }}</dt>
                        <dd class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="item in category.items" :key="item" class="rounded-md bg-muted px-2 py-0.5 text-xs text-foreground/85">{{
                                item
                            }}</span>
                        </dd>
                    </div>
                </dl>
                <p v-if="portfolio.familiarSkills.length" class="mt-4 text-sm text-muted-foreground">
                    Familiar with: {{ portfolio.familiarSkills.join(', ') }}
                </p>
            </section>

            <section v-if="portfolio.education.length" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Education</h2>
                <ul class="grid gap-4">
                    <li
                        v-for="item in portfolio.education"
                        :key="`${item.degree}-${item.school}`"
                        class="grid gap-1 sm:grid-cols-[10rem_1fr] sm:gap-6"
                    >
                        <p class="text-sm text-muted-foreground tabular-nums">{{ item.period }}</p>
                        <div>
                            <h3 class="font-medium">{{ item.degree }}</h3>
                            <p class="text-sm text-muted-foreground">{{ item.school }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <section v-if="portfolio.languages.length" class="border-b border-border py-8 last-of-type:border-b-0">
                <h2 class="mb-5 text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">Languages</h2>
                <p class="text-sm text-foreground/90">
                    <template v-for="(language, index) in portfolio.languages" :key="language.name"
                        >{{ index === 0 ? '' : ' · ' }}{{ language.name }} <span class="text-muted-foreground">({{ language.level }})</span></template
                    >
                </p>
            </section>

            <div v-if="page.props.hasResumePdf" class="mt-12 flex justify-center">
                <a
                    :href="pdf.url()"
                    download
                    class="inline-flex items-center gap-2 rounded-full border border-border px-5 py-2.5 text-sm font-medium transition-colors hover:border-emerald-500/40 hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    <Download class="size-4" aria-hidden="true" />
                    Download the PDF
                </a>
            </div>
        </div>
    </div>
</template>
