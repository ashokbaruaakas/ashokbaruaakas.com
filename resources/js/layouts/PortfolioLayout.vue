<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import PortfolioMobileNav from '@/components/portfolio/PortfolioMobileNav.vue';
import PortfolioNav from '@/components/portfolio/PortfolioNav.vue';
import PortfolioSideDots from '@/components/portfolio/PortfolioSideDots.vue';
import type { Portfolio } from '@/types/portfolio';

const page = usePage();
const portfolio = computed(() => page.props.portfolio as Portfolio);
</script>

<template>
    <div
        class="relative isolate overflow-x-clip bg-background text-foreground antialiased"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10"
        >
            <div
                class="absolute -top-40 right-[-10%] size-[600px] animate-[floatBlob_20s_ease-in-out_infinite] rounded-full bg-[radial-gradient(circle,rgba(16,185,129,0.18),transparent_70%)] blur-[100px]"
            />
            <div
                class="absolute bottom-[-20%] left-[-10%] size-[600px] animate-[floatBlob_26s_ease-in-out_infinite_reverse] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.15),transparent_70%)] blur-[100px]"
            />
            <div
                class="absolute top-1/2 left-1/2 size-[400px] -translate-x-1/2 -translate-y-1/2 animate-[floatBlob_30s_ease-in-out_infinite] rounded-full bg-[radial-gradient(circle,rgba(16,185,129,0.12),transparent_70%)] blur-[120px]"
            />
            <div
                class="absolute inset-0"
                :style="{
                    backgroundImage:
                        'radial-gradient(circle, var(--color-border) 1px, transparent 1px)',
                    backgroundSize: '32px 32px',
                }"
            />
            <div class="grain-overlay absolute inset-0" />
        </div>

        <PortfolioNav />
        <main>
            <slot />
        </main>
        <PortfolioFooter :portfolio="portfolio" />
        <PortfolioSideDots />
        <PortfolioMobileNav />
    </div>
</template>
