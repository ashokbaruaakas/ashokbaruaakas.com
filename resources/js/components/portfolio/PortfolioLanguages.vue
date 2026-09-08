<script setup lang="ts">
import { Languages as LanguagesIcon } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="languages"
        eyebrow="Languages"
        title="How I Communicate"
        description="Spoken languages I work and collaborate in."
    >
        <div class="flex flex-wrap gap-3">
            <div
                v-for="(language, index) in portfolio.languages"
                :key="index"
                class="inline-flex items-center gap-2.5 rounded-full border border-border bg-background/60 px-5 py-2.5 text-sm font-medium backdrop-blur-sm"
            >
                <LanguagesIcon
                    class="size-4 text-emerald-600 dark:text-emerald-400"
                />
                {{ language.name }}
                <span class="text-xs text-muted-foreground"
                    >({{ language.level }})</span
                >
            </div>
        </div>
    </PortfolioSection>
</template>
