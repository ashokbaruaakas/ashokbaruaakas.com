<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import PortfolioProjectCard from '@/components/portfolio/PortfolioProjectCard.vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import { openSource } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="work"
        eyebrow="Work"
        title="What I've Built"
        description="A selection of production systems, platforms, and tools I've built and maintained."
    >
        <div class="mb-4 flex items-center gap-3">
            <p
                class="text-xs font-semibold tracking-widest text-emerald-700 uppercase dark:text-emerald-400"
            >
                Flagship projects
            </p>
            <span class="h-px flex-1 bg-border" />
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <PortfolioProjectCard
                v-for="(project, index) in portfolio.projects.filter(
                    ({ tier }) => tier === 1,
                )"
                :key="project.name"
                :project="project"
                featured
                :class="{ 'md:col-span-2': index === 0 }"
            />
        </div>

        <div class="mt-12 mb-4 flex items-center gap-3">
            <p
                class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
            >
                Supporting projects
            </p>
            <span class="h-px flex-1 bg-border" />
        </div>
        <p class="-mt-2 mb-4 text-sm text-muted-foreground">
            Side projects built alongside full-time work
        </p>
        <div class="grid gap-6 md:grid-cols-3">
            <PortfolioProjectCard
                v-for="project in portfolio.projects.filter(
                    ({ tier }) => tier === 2,
                )"
                :key="project.name"
                :project="project"
            />
        </div>

        <a
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
            :href="openSource()"
            class="mt-4 ml-5 inline-flex items-center gap-1 font-medium text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            Explore Open-Source contributions
            <ArrowUpRight class="size-3.5" aria-hidden="true" />
        </Link>
    </PortfolioSection>
</template>
