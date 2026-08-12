<script setup lang="ts">
import { Briefcase, Rocket } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="about"
        eyebrow="About"
        title="Who I am"
        description="A brief look at what I work on and where my energy goes."
    >
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="rounded-xl border border-border bg-card p-6">
                <Briefcase
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                />
                <h3 class="mt-4 font-medium">Currently building</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ portfolio.currentWork }}
                </p>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <Rocket class="size-5 text-emerald-600 dark:text-emerald-400" />
                <h3 class="mt-4 font-medium">Side projects</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ portfolio.sideProjects }}
                </p>
            </div>
        </div>
    </PortfolioSection>
</template>
