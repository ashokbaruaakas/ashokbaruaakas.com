<script setup lang="ts">
import { computed, onMounted } from 'vue'
import PortfolioAbout from '@/components/portfolio/PortfolioAbout.vue'
import PortfolioCaseStudies from '@/components/portfolio/PortfolioCaseStudies.vue'
import PortfolioConnect from '@/components/portfolio/PortfolioConnect.vue'
import PortfolioEducation from '@/components/portfolio/PortfolioEducation.vue'
import PortfolioExperience from '@/components/portfolio/PortfolioExperience.vue'
import PortfolioHero from '@/components/portfolio/PortfolioHero.vue'
import PortfolioProjects from '@/components/portfolio/PortfolioProjects.vue'
import PortfolioSeoHead from '@/components/portfolio/PortfolioSeoHead.vue'
import PortfolioSkills from '@/components/portfolio/PortfolioSkills.vue'
import PortfolioTestimonials from '@/components/portfolio/PortfolioTestimonials.vue'
import { buildPersonSchema } from '@/lib/structuredData'
import type { Portfolio, SeoMetadata } from '@/types/portfolio'

const { portfolio, seo } = defineProps<{
    portfolio: Portfolio
    seo: SeoMetadata
}>()

const sectionComponents = [
    { id: 'about', component: PortfolioAbout },
    { id: 'skills', component: PortfolioSkills },
    { id: 'work', component: PortfolioProjects },
    { id: 'case-studies', component: PortfolioCaseStudies },
    { id: 'experience', component: PortfolioExperience },
    { id: 'education', component: PortfolioEducation },
    { id: 'testimonials', component: PortfolioTestimonials },
    { id: 'connect', component: PortfolioConnect },
]

const sections = computed(() => sectionComponents.filter(({ id }) => portfolio.visibleSections.includes(id)))

const structuredData = computed(() => [
    buildPersonSchema(portfolio, seo),
    {
        '@type': 'WebSite',
        '@id': `${seo.homeUrl}#website`,
        name: portfolio.name,
        url: seo.homeUrl,
        publisher: { '@id': `${seo.homeUrl}#person` },
    },
])

onMounted(() => {
    const sectionId = decodeURIComponent(window.location.hash.slice(1))

    if (sectionId) {
        document.getElementById(sectionId)?.scrollIntoView()
    }
})
</script>

<template>
    <PortfolioSeoHead :portfolio="portfolio" :seo="seo" page="home" :structured-data="structuredData" />

    <PortfolioHero :portfolio="portfolio" />
    <div class="px-6">
        <div class="mx-auto flex max-w-5xl flex-col gap-32 pb-16 lg:gap-40">
            <template v-for="(section, index) in sections" :key="section.id">
                <div
                    v-if="index > 0"
                    aria-hidden="true"
                    class="-mt-20 h-px max-w-xs self-center bg-gradient-to-r from-transparent via-emerald-500/20 to-transparent lg:-mt-24"
                />
                <component :is="section.component" :portfolio="portfolio" />
            </template>
        </div>
    </div>
</template>
