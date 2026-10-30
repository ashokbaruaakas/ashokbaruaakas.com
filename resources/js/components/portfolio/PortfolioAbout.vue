<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Rocket, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import { openSource } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

/**
 * Split the side projects copy so handles like @thesoftpulze can be highlighted.
 */
const sideProjectSegments = computed(() =>
    props.portfolio.sideProjects.split(/(@[a-z0-9_-]+)/i),
);

const isHandle = (segment: string): boolean => segment.startsWith('@');
</script>

<template>
    <PortfolioSection
        id="about"
        eyebrow="About"
        title="Who I Am"
        description="How I work and what I'm looking for next."
    >
        <div class="grid gap-6 lg:grid-cols-3">
            <div
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm lg:col-span-2"
            >
                <UserRound
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <p class="mt-4 leading-relaxed text-muted-foreground">
                    {{ portfolio.about }}
                </p>
            </div>

            <Link
                :href="openSource()"
                class="group flex flex-col rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5 focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:outline-none"
            >
                <div class="flex items-start justify-between gap-4">
                    <Rocket
                        class="size-5 text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
                    />
                    <ArrowUpRight
                        class="size-4 shrink-0 text-muted-foreground transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                        aria-hidden="true"
                    />
                </div>
                <h3 class="mt-4 font-medium">Side projects</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    <span
                        v-for="(segment, index) in sideProjectSegments"
                        :key="index"
                        :class="
                            isHandle(segment)
                                ? 'font-medium text-emerald-400'
                                : undefined
                        "
                        >{{ segment }}</span
                    >
                </p>
            </Link>
        </div>
    </PortfolioSection>
</template>
