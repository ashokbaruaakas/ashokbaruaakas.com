<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Briefcase } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import { openSource } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="experience"
        eyebrow="Experience"
        title="Where I've Worked"
        description="Roles and contributions that shape how I build software."
    >
        <div class="grid gap-6 lg:grid-cols-5">
            <ol
                class="relative space-y-8 border-l border-border pl-6 lg:col-span-3"
            >
                <li
                    v-for="entry in portfolio.experience"
                    :key="`${entry.company}-${entry.role}`"
                    class="relative"
                >
                    <span
                        class="absolute -left-[31px] flex size-3 items-center justify-center"
                    >
                        <span
                            class="size-3 rounded-full border-2 border-emerald-500 bg-background"
                        />
                    </span>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <h3 class="font-medium">{{ entry.role }}</h3>
                        <a
                            v-if="entry.url"
                            :href="entry.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-sm text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >
                            {{ entry.company }}
                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            <span class="sr-only">(opens in a new tab)</span>
                        </a>
                        <span v-else class="text-sm text-muted-foreground">
                            {{ entry.company }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ entry.description }}
                    </p>
                    <ul
                        v-if="entry.highlights.length"
                        class="mt-3 space-y-1.5 border-l border-border pl-4"
                    >
                        <li
                            v-for="(highlight, index) in entry.highlights"
                            :key="index"
                            class="relative text-sm text-muted-foreground"
                        >
                            <span
                                class="absolute top-2 -left-[21px] size-1.5 rounded-full bg-emerald-500"
                                aria-hidden="true"
                            />
                            {{ highlight }}
                        </li>
                    </ul>
                    <p
                        class="mt-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ entry.period }}
                    </p>
                </li>
            </ol>

            <div class="lg:col-span-2">
                <Link
                    :href="openSource()"
                    class="group flex items-start gap-3 rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5"
                >
                    <Briefcase
                        class="mt-0.5 size-5 text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
                    />
                    <div>
                        <div
                            class="flex items-center justify-between gap-x-3 gap-y-1"
                        >
                            <h3 class="font-medium">Open Source</h3>
                            <span
                                class="inline-flex items-center gap-1 font-medium text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                            >
                                View contributions
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </span>
                        </div>
                        <ul class="mt-3 space-y-1.5">
                            <li
                                class="relative pl-4 text-sm text-muted-foreground"
                            >
                                <span
                                    class="absolute top-2 left-0 size-1.5 rounded-full bg-emerald-600"
                                    aria-hidden="true"
                                />
                                Merged contributions to Inertia.js and Raycast
                            </li>
                            <li
                                class="relative pl-4 text-sm text-muted-foreground"
                            >
                                <span
                                    class="absolute top-2 left-0 size-1.5 rounded-full bg-emerald-600"
                                    aria-hidden="true"
                                />
                                Built and maintain open-source projects under
                                SoftPulze, including LaraVibe Standards,
                                LaraVibe Vue, and Clawkit
                            </li>
                        </ul>
                    </div>
                </Link>
            </div>
        </div>
    </PortfolioSection>
</template>
