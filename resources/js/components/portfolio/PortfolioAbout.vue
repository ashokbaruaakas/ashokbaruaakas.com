<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Briefcase, Rocket } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import { openSource } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="about"
        eyebrow="About"
        title="Who I Am"
        description="A brief look at what I work on and where my energy goes."
    >
        <div class="grid gap-6 sm:grid-cols-2">
            <div
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <Briefcase
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                />
                <h3 class="mt-4 font-medium">Currently building</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ portfolio.currentWork }}
                </p>
            </div>

            <Link
                :href="openSource()"
                class="group flex flex-col rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5"
            >
                <Rocket class="size-5 text-emerald-600 dark:text-emerald-400" />
                <h3 class="mt-4 font-medium">Side projects</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ portfolio.sideProjects }}
                </p>
            </Link>
        </div>
    </PortfolioSection>
</template>
