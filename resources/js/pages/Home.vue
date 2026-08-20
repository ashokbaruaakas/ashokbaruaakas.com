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
                <PortfolioConnect :portfolio="portfolio" />
            </div>
        </div>
    </PortfolioLayout>
</template>
