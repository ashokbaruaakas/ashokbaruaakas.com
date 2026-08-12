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
    <div class="min-h-screen bg-background text-foreground antialiased">
        <PortfolioNav />
        <main>
            <slot />
        </main>
        <PortfolioFooter :portfolio="portfolio" />
        <PortfolioSideDots />
        <PortfolioMobileNav />
    </div>
</template>
