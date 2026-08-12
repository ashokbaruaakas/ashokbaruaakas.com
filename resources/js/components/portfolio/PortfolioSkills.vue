<script setup lang="ts">
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="skills"
        eyebrow="Skills"
        title="My toolbox"
        description="The languages, frameworks, tools, and platforms I work with day to day."
    >
        <div class="grid gap-8 sm:grid-cols-2">
            <div
                v-for="category in portfolio.skills"
                :key="category.category"
                class="rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <h3 class="font-medium">{{ category.category }}</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-for="item in category.items"
                        :key="item"
                        class="inline-flex items-center rounded-md border border-border bg-background px-3 py-1 text-xs font-medium text-foreground"
                    >
                        {{ item }}
                    </span>
                </div>
            </div>
        </div>
    </PortfolioSection>
</template>
