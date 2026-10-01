<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h } from 'vue';
import PortfolioAbout from '@/components/portfolio/PortfolioAbout.vue';
import PortfolioConnect from '@/components/portfolio/PortfolioConnect.vue';
import PortfolioEducation from '@/components/portfolio/PortfolioEducation.vue';
import PortfolioExperience from '@/components/portfolio/PortfolioExperience.vue';
import PortfolioHero from '@/components/portfolio/PortfolioHero.vue';
import PortfolioProjects from '@/components/portfolio/PortfolioProjects.vue';
import PortfolioSkills from '@/components/portfolio/PortfolioSkills.vue';
import type { Portfolio, SeoMetadata } from '@/types/portfolio';

const { portfolio, seo } = defineProps<{
    portfolio: Portfolio;
    seo: SeoMetadata;
}>();

const pageTitle = 'Senior Full-Stack Engineer | Fintech & Multi-Tenant SaaS';
const shareTitle = `${portfolio.name} — ${pageTitle}`;
const pageDescription =
    'Senior full-stack engineer focused on fintech payments and multi-tenant SaaS, with SiPay, HSM, card-saving, and 40,000+ customer platform experience.';

const structuredData = computed(() => {
    const [city, country] = portfolio.location
        .split(',')
        .map((part) => part.trim());
    const currentEmployer = portfolio.experience.find((entry) =>
        entry.period.includes('Present'),
    )?.company;

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'Person',
                '@id': `${seo.homeUrl}#person`,
                name: portfolio.name,
                alternateName: ['Ashok Barua Akas', 'Akas'],
                jobTitle: 'Senior Full-Stack Engineer',
                url: seo.homeUrl,
                image: seo.imageUrl,
                email: portfolio.email,
                address: {
                    '@type': 'PostalAddress',
                    addressLocality: city,
                    addressCountry: country,
                },
                sameAs: portfolio.socialLinks
                    .filter(({ platform }) =>
                        ['GitHub', 'X', 'Telegram'].includes(platform),
                    )
                    .map(({ url }) => url),
                alumniOf: portfolio.education.map(({ school }) => ({
                    '@type': 'EducationalOrganization',
                    name: school.split(' - ')[0],
                })),
                knowsAbout: [
                    ...new Set([
                        ...portfolio.skills.flatMap(({ items }) => items),
                        ...portfolio.projects.flatMap(
                            ({ technologies }) => technologies,
                        ),
                    ]),
                ],
                ...(currentEmployer
                    ? {
                          worksFor: {
                              '@type': 'Organization',
                              name: currentEmployer,
                          },
                      }
                    : {}),
            },
            {
                '@type': 'WebSite',
                '@id': `${seo.homeUrl}#website`,
                name: portfolio.name,
                url: seo.homeUrl,
                publisher: { '@id': `${seo.homeUrl}#person` },
            },
        ],
    }).replace(/</g, '\\u003c');
});

const structuredDataTag = () =>
    h(
        'script',
        {
            'head-key': 'structured-data',
            type: 'application/ld+json',
        },
        structuredData.value,
    );
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
        <component :is="structuredDataTag" />
    </Head>

    <PortfolioHero :portfolio="portfolio" />
    <div class="px-6">
        <div class="mx-auto flex max-w-5xl flex-col gap-32 pb-16 lg:gap-40">
            <PortfolioAbout :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioSkills :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioProjects :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioExperience :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioEducation :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioConnect :portfolio="portfolio" />
        </div>
    </div>
</template>
