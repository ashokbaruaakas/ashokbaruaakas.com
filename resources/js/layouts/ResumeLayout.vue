<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import PortfolioBackground from '@/components/portfolio/PortfolioBackground.vue';
import PortfolioNav from '@/components/portfolio/PortfolioNav.vue';
</script>

<template>
    <div
        class="relative isolate overflow-clip bg-background text-foreground antialiased"
    >
        <PortfolioBackground />
        <PortfolioNav />
        <main class="px-6 pt-24 pb-16 lg:px-12">
            <div class="mx-auto w-full max-w-[210mm]">
                <Link
                    href="/"
                    class="mb-8 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Back to portfolio
                </Link>
                <slot />
            </div>
        </main>
    </div>
</template>
