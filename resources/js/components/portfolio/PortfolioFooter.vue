<script setup lang="ts">
import { ArrowUp, Heart } from '@lucide/vue';
import { computed } from 'vue';
import { getScrollBehavior } from '@/composables/useScrollSpy';
import type { Portfolio } from '@/types/portfolio';

withDefaults(
    defineProps<{
        portfolio: Portfolio;
        reserveMobileNavSpace?: boolean;
    }>(),
    { reserveMobileNavSpace: false },
);

const year = computed(() => new Date().getFullYear());

function scrollToTop(): void {
    window.scrollTo({ top: 0, behavior: getScrollBehavior() });
}
</script>

<template>
    <footer class="relative border-t border-emerald-500/10">
        <div
            class="mx-auto flex max-w-5xl flex-col items-center gap-4 px-6 py-10 text-sm text-muted-foreground sm:flex-row sm:justify-between"
            :class="{ 'pb-28 lg:pb-10': reserveMobileNavSpace }"
        >
            <p class="order-2 text-center sm:order-1 sm:text-left">
                &copy; {{ year }}
                <span
                    class="bg-gradient-to-r from-emerald-700 to-teal-700 bg-clip-text font-medium text-transparent dark:from-emerald-400 dark:to-teal-300"
                >
                    {{ portfolio.name }}
                </span>
                <span class="px-1 text-border">·</span>{{ portfolio.location }}
            </p>

            <button
                type="button"
                aria-label="Back to top"
                title="Back to top"
                class="order-1 inline-flex size-9 items-center justify-center rounded-full border border-border bg-background/50 text-muted-foreground backdrop-blur transition-colors hover:border-emerald-500/40 hover:text-emerald-600 sm:order-2"
                @click="scrollToTop"
            >
                <ArrowUp class="size-4" aria-hidden="true" />
            </button>

            <p
                class="order-3 flex flex-col items-center gap-1 text-center sm:items-end sm:text-right"
            >
                <span class="inline-flex items-center gap-1.5">
                    Crafted with
                    <Heart
                        class="size-3.5 fill-emerald-500 text-emerald-500"
                        aria-hidden="true"
                    />
                    <span class="font-mono">Laravel · Inertia · Vue</span>
                </span>
            </p>
        </div>
    </footer>
</template>
