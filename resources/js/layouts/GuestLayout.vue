<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioBackground from '@/components/portfolio/PortfolioBackground.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import PortfolioKeywordMarquee from '@/components/portfolio/PortfolioKeywordMarquee.vue';
import PortfolioMobileNav from '@/components/portfolio/PortfolioMobileNav.vue';
import PortfolioNav from '@/components/portfolio/PortfolioNav.vue';
import PortfolioSideDots from '@/components/portfolio/PortfolioSideDots.vue';
import type { Portfolio } from '@/types/portfolio';

const page = usePage();
const portfolio = computed(() => page.props.portfolio as Portfolio);
const isHome = computed(() => page.component === 'Home');
</script>

<template>
    <div
        class="relative isolate overflow-clip bg-background text-foreground antialiased print:!bg-white"
    >
        <PortfolioBackground class="print:hidden" />
        <PortfolioNav class="print:hidden" />
        <main>
            <slot />
        </main>
        <PortfolioFooter
            :portfolio="portfolio"
            :reserve-mobile-nav-space="isHome"
            class="print:hidden"
        />
        <PortfolioKeywordMarquee class="print:hidden" />
        <PortfolioSideDots v-if="isHome" class="print:hidden" />
        <PortfolioMobileNav v-if="isHome" class="print:hidden" />
    </div>
</template>
