<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h, onMounted } from 'vue';
import PortfolioAbout from '@/components/portfolio/PortfolioAbout.vue';
import PortfolioCaseStudies from '@/components/portfolio/PortfolioCaseStudies.vue';
import PortfolioConnect from '@/components/portfolio/PortfolioConnect.vue';
import PortfolioEducation from '@/components/portfolio/PortfolioEducation.vue';
import PortfolioExperience from '@/components/portfolio/PortfolioExperience.vue';
import PortfolioHero from '@/components/portfolio/PortfolioHero.vue';
import PortfolioProjects from '@/components/portfolio/PortfolioProjects.vue';
import PortfolioSkills from '@/components/portfolio/PortfolioSkills.vue';
import PortfolioTestimonials from '@/components/portfolio/PortfolioTestimonials.vue';
import {
    buildPersonSchema,
    serializeStructuredData,
} from '@/lib/structuredData';
import type { Portfolio, SeoMetadata } from '@/types/portfolio';

const { portfolio, seo } = defineProps<{
    portfolio: Portfolio;
    seo: SeoMetadata;
}>();

const pageTitle = 'Senior Full-Stack Engineer | Fintech & Business Platforms';
const shareTitle = `${portfolio.name} — ${pageTitle}`;
const pageDescription =
    'Senior full-stack engineer focused on fintech payments and business platforms, with SiPay, HSM, card-saving, and end-to-end business platform experience.';

const structuredData = computed(() =>
    serializeStructuredData([
        buildPersonSchema(portfolio, seo),
        {
            '@type': 'WebSite',
            '@id': `${seo.homeUrl}#website`,
            name: portfolio.name,
            url: seo.homeUrl,
            publisher: { '@id': `${seo.homeUrl}#person` },
        },
    ]),
);

const structuredDataTag = () =>
    h(
        'script',
        {
            'head-key': 'structured-data',
            type: 'application/ld+json',
        },
        structuredData.value,
    );

onMounted(() => {
    const sectionId = decodeURIComponent(window.location.hash.slice(1));

    if (sectionId) {
        document.getElementById(sectionId)?.scrollIntoView();
    }
});
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
            <PortfolioCaseStudies :portfolio="portfolio" />
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
            <PortfolioTestimonials :portfolio="portfolio" />
            <div
                aria-hidden="true"
                class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
            />
            <PortfolioConnect :portfolio="portfolio" />
        </div>
    </div>
</template>
