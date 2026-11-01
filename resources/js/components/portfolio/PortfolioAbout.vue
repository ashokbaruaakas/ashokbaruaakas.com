<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Rocket, UserRound, Workflow } from '@lucide/vue';
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
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
                :class="portfolio.howIWork ? 'lg:col-span-2' : 'lg:col-span-3'"
            >
                <UserRound
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <p class="mt-4 leading-relaxed text-muted-foreground">
                    {{ portfolio.about }}
                </p>
            </div>

            <div
                v-if="portfolio.howIWork"
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <Workflow
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <h3 class="mt-4 font-medium">How I work</h3>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                    {{ portfolio.howIWork }}
                </p>
            </div>

            <Link
                :href="openSource()"
                class="group flex items-start gap-4 rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5 focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:outline-none lg:col-span-3"
            >
                <Rocket
                    class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <div class="flex-1">
                    <h3 class="font-medium">Open source</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
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
                </div>
                <ArrowUpRight
                    class="size-4 shrink-0 text-muted-foreground transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                    aria-hidden="true"
                />
            </Link>
        </div>
    </PortfolioSection>
</template>
