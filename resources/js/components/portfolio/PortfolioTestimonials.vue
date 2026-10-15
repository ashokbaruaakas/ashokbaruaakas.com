<script setup lang="ts">
import { Quote } from '@lucide/vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <PortfolioSection
        id="testimonials"
        eyebrow="Testimonials"
        title="What Colleagues Say"
        description="Feedback from people I have built with."
    >
        <div class="grid gap-6 sm:grid-cols-2">
            <figure
                v-for="(testimonial, index) in portfolio.testimonials"
                :key="index"
                class="flex flex-col rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm"
            >
                <Quote
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <blockquote class="mt-3 flex-1 text-sm leading-relaxed">
                    “{{ testimonial.quote }}”
                </blockquote>
                <figcaption class="mt-5 text-sm">
                    <span class="font-medium">{{ testimonial.author }}</span>
                    <span class="block text-muted-foreground">
                        {{ testimonial.role }}, {{ testimonial.company }}
                    </span>
                </figcaption>
            </figure>
        </div>
    </PortfolioSection>
</template>
