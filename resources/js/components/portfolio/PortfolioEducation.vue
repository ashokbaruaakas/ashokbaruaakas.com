<script setup lang="ts">
import { GraduationCap, Languages as LanguagesIcon } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="education"
        eyebrow="Education"
        title="What I Studied"
        description="The formal groundwork behind the hands-on experience."
    >
        <div class="grid gap-6 sm:grid-cols-2">
            <div
                v-for="(item, index) in portfolio.education"
                :key="index"
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <div class="flex items-start gap-3">
                    <GraduationCap
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div class="min-w-0">
                        <h3 class="font-medium">{{ item.degree }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ item.school }}
                        </p>
                        <p
                            class="mt-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            {{ item.period }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <div class="flex items-start gap-3">
                    <LanguagesIcon
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div class="min-w-0">
                        <h3 class="font-medium">Languages</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="(language, index) in portfolio.languages"
                                :key="index"
                                class="inline-flex items-center gap-1.5 rounded-full border border-border bg-background px-3 py-1 text-xs font-medium text-foreground"
                            >
                                {{ language.name }}
                                <span class="text-muted-foreground"
                                    >({{ language.level }})</span
                                >
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PortfolioSection>
</template>
