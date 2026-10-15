<script setup lang="ts">
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { CaseStudy, Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();

const parts: { key: keyof CaseStudy; label: string }[] = [
    { key: 'constraints', label: 'Constraints' },
    { key: 'design', label: 'Design' },
    { key: 'tradeoffs', label: 'Trade-offs' },
    { key: 'results', label: 'Results' },
];
</script>

<template>
    <PortfolioSection
        id="case-studies"
        eyebrow="Case Studies"
        title="How I Approach Hard Problems"
        description="The problem, the constraints, the design I chose, and what it achieved."
    >
        <div class="grid gap-6 lg:grid-cols-2">
            <article
                v-for="study in portfolio.caseStudies"
                :key="study.title"
                class="flex flex-col rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ study.context }}
                </p>
                <h3 class="mt-2 text-lg font-medium">{{ study.title }}</h3>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ study.problem }}
                </p>

                <div v-for="part in parts" :key="part.key" class="mt-5">
                    <h4
                        class="text-sm font-medium text-emerald-700 dark:text-emerald-400"
                    >
                        {{ part.label }}
                    </h4>
                    <ul class="mt-2 space-y-1.5 border-l border-border pl-4">
                        <li
                            v-for="(item, index) in study[part.key] as string[]"
                            :key="index"
                            class="relative text-sm text-muted-foreground"
                        >
                            <span
                                class="absolute top-2 -left-[21px] size-1.5 rounded-full bg-emerald-500"
                                aria-hidden="true"
                            />
                            {{ item }}
                        </li>
                    </ul>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <span
                        v-for="technology in study.technologies"
                        :key="technology"
                        class="rounded-full border border-border bg-background px-3 py-1 text-xs font-medium"
                    >
                        {{ technology }}
                    </span>
                </div>
            </article>
        </div>
    </PortfolioSection>
</template>
