<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { type Component, computed, h } from 'vue'
import PortfolioBackLink from '@/components/portfolio/PortfolioBackLink.vue'
import ResumeDownloadButton from '@/components/portfolio/ResumeDownloadButton.vue'
import ResumeSheet from '@/components/portfolio/ResumeSheet.vue'
import { useIsSignedIn } from '@/composables/useIsSignedIn'
import { buildPersonSchema, serializeStructuredData } from '@/lib/structuredData'
import type { Portfolio, SeoMetadata } from '@/types/portfolio'
import Classic from './templates/Classic.vue'

const { portfolio, seo, template } = defineProps<{
    portfolio: Portfolio
    seo: SeoMetadata
    template: string
}>()

const templates: Record<string, Component> = { classic: Classic }

const templateComponent = computed(() => templates[template] ?? Classic)

const isSignedIn = useIsSignedIn()

const pageContent = portfolio.pages.resume
const pageTitle = pageContent.title
const shareTitle = `${portfolio.name} — ${pageTitle}`
const pageDescription = pageContent.description

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

    <div class="px-4 pt-24 pb-16 sm:px-6 lg:px-12 print:!p-0">
        <div class="mx-auto w-full max-w-[210mm]">
            <PortfolioBackLink />

            <ResumeSheet :show-page-guides="isSignedIn">
                <component :is="templateComponent" :portfolio="portfolio" />
            </ResumeSheet>

            <ResumeDownloadButton class="mt-6" />
        </div>
    </div>
</template>
