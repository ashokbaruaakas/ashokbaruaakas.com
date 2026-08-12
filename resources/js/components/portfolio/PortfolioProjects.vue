<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import PortfolioProjectCard from '@/components/portfolio/PortfolioProjectCard.vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="projects"
        eyebrow="Work"
        title="What I've Built"
        description="Open-source work and tools I've built and contributed to."
    >
        <div class="grid gap-6 md:grid-cols-2">
            <PortfolioProjectCard
                v-for="project in portfolio.projects"
                :key="`${project.owner}/${project.repo}`"
                :project="project"
            />
        </div>

        <a
            :href="`https://github.com/${portfolio.githubUsername}`"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-8 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 transition-colors hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            View all on GitHub
            <ArrowUpRight class="size-4" />
        </a>
    </PortfolioSection>
</template>
