<script setup lang="ts">
import { Download } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, ref } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

const anchor = ref<HTMLElement | null>(null);

/** True only when the in-flow anchor is fully on screen, so a partially visible anchor never renders a half-cut button. */
const anchorIsFullyVisible = ref(false);

useIntersectionObserver(
    anchor,
    ([entry]) => {
        anchorIsFullyVisible.value =
            entry?.isIntersecting && entry.intersectionRatio >= 0.99;
    },
    { threshold: [0, 1] },
);

/** The button floats at the bottom-right of the viewport whenever its in-flow anchor is not directly visible. */
const isFloating = computed(() => !anchorIsFullyVisible.value);

function printResume(): void {
    window.print();
}
</script>

<template>
    <div ref="anchor" class="flex min-h-10 justify-center print:hidden">
        <TooltipProvider :delay-duration="0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        aria-label="Download PDF"
                        class="inline-flex size-10 cursor-pointer items-center justify-center rounded-full border border-border bg-background/60 text-foreground backdrop-blur transition-colors hover:border-emerald-500/40 hover:text-emerald-600 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="{
                            'fixed right-1/2 bottom-4 z-50 translate-x-1/2 animate-in shadow-lg duration-200 fade-in-0 slide-in-from-bottom-2 lg:bottom-6':
                                isFloating,
                        }"
                        @click="printResume"
                    >
                        <Download class="size-4" aria-hidden="true" />
                    </button>
                </TooltipTrigger>
                <TooltipContent>Download PDF</TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
