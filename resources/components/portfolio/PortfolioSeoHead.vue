<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, h } from 'vue'
import { serializeStructuredData } from '@/lib/structuredData'
import type { Portfolio, SeoMetadata } from '@/types/portfolio'

const { portfolio, seo, page, structuredData } = defineProps<{
    portfolio: Portfolio
    seo: SeoMetadata
    page: keyof Portfolio['pages']
    structuredData: unknown[]
}>()

const pageContent = computed(() => portfolio.pages[page])
const shareTitle = computed(() => `${portfolio.name} — ${pageContent.value.title}`)

const structuredDataTag = () =>
    h(
        'script',
        {
            'head-key': 'structured-data',
            type: 'application/ld+json',
        },
        serializeStructuredData(structuredData),
    )
</script>

<template>
    <Head :title="pageContent.title">
        <meta v-if="pageContent.description" head-key="description" name="description" :content="pageContent.description" />
        <link head-key="canonical" rel="canonical" :href="seo.canonicalUrl" />
        <meta head-key="og:title" property="og:title" :content="shareTitle" />
        <meta v-if="pageContent.description" head-key="og:description" property="og:description" :content="pageContent.description" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:url" property="og:url" :content="seo.canonicalUrl" />
        <meta head-key="og:site_name" property="og:site_name" :content="portfolio.name" />
        <template v-if="seo.image">
            <meta head-key="og:image" property="og:image" :content="seo.image.url" />
            <meta head-key="og:image:secure_url" property="og:image:secure_url" :content="seo.image.url" />
            <meta head-key="og:image:type" property="og:image:type" :content="seo.image.type" />
            <meta head-key="og:image:width" property="og:image:width" :content="String(seo.image.width)" />
            <meta head-key="og:image:height" property="og:image:height" :content="String(seo.image.height)" />
            <meta head-key="og:image:alt" property="og:image:alt" :content="pageContent.imageAlt" />
        </template>
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="shareTitle" />
        <meta v-if="pageContent.description" head-key="twitter:description" name="twitter:description" :content="pageContent.description" />
        <template v-if="seo.image">
            <meta head-key="twitter:image" name="twitter:image" :content="seo.image.url" />
            <meta head-key="twitter:image:alt" name="twitter:image:alt" :content="pageContent.imageAlt" />
        </template>
        <component :is="structuredDataTag" />
    </Head>
</template>
