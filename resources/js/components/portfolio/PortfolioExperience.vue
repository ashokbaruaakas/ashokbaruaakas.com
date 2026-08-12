<script setup lang="ts">
import {
    Award,
    ArrowUpRight,
    Briefcase,
    GitPullRequest,
    Rocket,
    Swords,
    Zap,
} from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();

const achievements = [
    { label: 'Pull Shark', detail: '×3', Icon: GitPullRequest },
    { label: 'Pair Extraordinaire', detail: '', Icon: Swords },
    { label: 'Quickdraw', detail: '', Icon: Zap },
    { label: 'YOLO', detail: '', Icon: Rocket },
];
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
                            class="inline-flex items-center gap-1 text-sm text-emerald-700 transition-colors hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >
                            {{ entry.company }}
                            <ArrowUpRight class="size-3.5" />
                        </a>
                        <span v-else class="text-sm text-muted-foreground">
                            {{ entry.company }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ entry.description }}
                    </p>
                    <p
                        class="mt-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ entry.period }}
                    </p>
                </li>
            </ol>

            <div class="lg:col-span-2">
                <div
                    class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
                >
                    <div class="flex items-center gap-2">
                        <Award
                            class="size-5 text-emerald-600 dark:text-emerald-400"
                        />
                        <h3 class="font-medium">GitHub achievements</h3>
                    </div>
                    <ul class="mt-4 grid grid-cols-2 gap-3">
                        <li
                            v-for="{ label, detail, Icon } in achievements"
                            :key="label"
                            class="flex items-center gap-2.5 rounded-lg border border-border bg-background px-3 py-2.5"
                        >
                            <component
                                :is="Icon"
                                class="size-4 text-emerald-600 dark:text-emerald-400"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ label }}
                                </p>
                                <p
                                    v-if="detail"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ detail }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="mt-6 flex items-start gap-3 rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
                >
                    <Briefcase
                        class="mt-0.5 size-5 text-emerald-600 dark:text-emerald-400"
                    />
                    <div>
                        <h3 class="font-medium">Open source</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Active contributor and maintainer across the
                            {{ portfolio.organization }} ecosystem.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PortfolioSection>
</template>
