<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PortfolioAbout from '@/components/portfolio/PortfolioAbout.vue';
import PortfolioConnect from '@/components/portfolio/PortfolioConnect.vue';
import PortfolioExperience from '@/components/portfolio/PortfolioExperience.vue';
import PortfolioHero from '@/components/portfolio/PortfolioHero.vue';
import PortfolioProjects from '@/components/portfolio/PortfolioProjects.vue';
import PortfolioSkills from '@/components/portfolio/PortfolioSkills.vue';
import PortfolioLayout from '@/layouts/PortfolioLayout.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <Head title="Ashok Barua Akas — Full-stack Engineer" />

    <PortfolioLayout>
        <PortfolioHero :portfolio="portfolio" />
        <div class="px-6">
            <div class="mx-auto flex max-w-5xl flex-col gap-24 pb-24 lg:gap-32">
                <PortfolioAbout :portfolio="portfolio" />
                <PortfolioSkills :portfolio="portfolio" />
                <PortfolioProjects :portfolio="portfolio" />
                <PortfolioExperience :portfolio="portfolio" />
                <PortfolioConnect :portfolio="portfolio" />
            </div>
        </div>
    </PortfolioLayout>
</template>
