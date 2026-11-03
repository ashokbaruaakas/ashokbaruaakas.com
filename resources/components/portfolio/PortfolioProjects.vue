<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowDown, ArrowUpRight } from '@lucide/vue'
import { computed } from 'vue'
import PortfolioProjectCard from '@/components/portfolio/PortfolioProjectCard.vue'
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue'
import { openSource } from '@/routes'
import type { Portfolio } from '@/types/portfolio'

const props = defineProps<{ portfolio: Portfolio }>()

const labels = computed(() => props.portfolio.sections.work.labels)

const flagshipProjects = computed(() => props.portfolio.projects.filter(({ tier }) => tier === 1))

const supportingProjects = computed(() => props.portfolio.projects.filter(({ tier }) => tier === 2))
</script>

<template>
    <PortfolioSection
        id="work"
        :eyebrow="portfolio.sections.work.eyebrow"
        :title="portfolio.sections.work.title"
        :description="portfolio.sections.work.description"
    >
        <div v-if="flagshipProjects.length" class="mb-4 flex items-center gap-3">
            <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase dark:text-emerald-400">
                {{ labels.flagship }}
            </p>
            <span class="h-px flex-1 bg-border" />
        </div>
        <div v-if="flagshipProjects.length" class="grid gap-6 md:grid-cols-2">
            <PortfolioProjectCard
                v-for="(project, index) in flagshipProjects"
                :key="project.name"
                :project="project"
                featured
                :class="{ 'md:col-span-2': index === 0 }"
            />
        </div>
        <a
            v-if="flagshipProjects.length && portfolio.caseStudies.length && labels.caseStudiesLink"
            href="#case-studies"
            class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            {{ labels.caseStudiesLink }}
            <ArrowDown class="size-4" aria-hidden="true" />
        </a>

        <div v-if="supportingProjects.length && labels.supporting" class="mt-12 mb-4 flex items-center gap-3">
            <p class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                {{ labels.supporting }}
            </p>
            <span class="h-px flex-1 bg-border" />
        </div>
        <p v-if="supportingProjects.length && labels.supportingDescription" class="-mt-2 mb-4 text-sm text-muted-foreground">
            {{ labels.supportingDescription }}
        </p>
        <div v-if="supportingProjects.length" class="grid gap-6 md:grid-cols-2">
            <PortfolioProjectCard v-for="project in supportingProjects" :key="project.name" :project="project" />
        </div>

        <a
            v-if="portfolio.githubUsername"
            :href="`https://github.com/${portfolio.githubUsername}`"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-8 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            View all on GitHub
            <ArrowUpRight class="size-4" aria-hidden="true" />
            <span class="sr-only">(opens in a new tab)</span>
        </a>
        <Link
            v-if="portfolio.hasOpenSource"
            :href="openSource()"
            class="mt-4 ml-5 inline-flex items-center gap-1 font-medium text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            Explore Open-Source contributions
            <ArrowUpRight class="size-3.5" aria-hidden="true" />
        </Link>
    </PortfolioSection>
</template>
