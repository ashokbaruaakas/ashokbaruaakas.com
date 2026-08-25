<script setup lang="ts">
import { ArrowUp, Heart } from '@lucide/vue';
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useScrollSpy } from '@/composables/useScrollSpy';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();

const { scrollTo } = useScrollSpy();
const year = computed(() => new Date().getFullYear());
</script>

<template>
    <footer class="relative border-t border-emerald-500/10">
        <div
            class="mx-auto flex max-w-5xl flex-col items-center gap-4 px-6 py-10 text-sm text-muted-foreground sm:flex-row sm:justify-between"
        >
            <p class="order-2 text-left sm:order-1">
                &copy; {{ year }}
                <span
                    class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text font-medium text-transparent"
                >
                    {{ portfolio.name }}
                </span>
                <span class="px-1 text-border">·</span>{{ portfolio.location }}
            </p>

            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            aria-label="Back to top"
                            class="order-1 inline-flex size-9 items-center justify-center rounded-full border border-border bg-background/50 text-muted-foreground backdrop-blur transition-colors hover:border-emerald-500/40 hover:text-emerald-500 sm:order-2"
                            @click="scrollTo('hero')"
                        >
                            <ArrowUp class="size-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>Back To Top</TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <p class="order-3 flex flex-col items-end gap-1 text-right">
                <span class="inline-flex items-center gap-1.5">
                    Crafted with
                    <Heart class="size-3.5 fill-emerald-500 text-emerald-500" />
                    <span class="font-mono">Laravel · Inertia · Vue</span>
                </span>
            </p>
        </div>
    </footer>
</template>
