<script setup lang="ts">
import BrandHeader from '@/components/layout/BrandHeader.vue'
import PortfolioBackground from '@/components/portfolio/PortfolioBackground.vue'
import { home } from '@/routes'
import { Link } from '@inertiajs/vue3'
import { ArrowLeft } from '@lucide/vue'

defineProps<{
    heading?: string
    subheading?: string
}>()
</script>

<template>
    <div class="relative isolate flex min-h-svh flex-col bg-sidebar text-foreground antialiased">
        <PortfolioBackground />

        <BrandHeader />

        <main class="flex flex-1 flex-col items-center justify-center gap-2 px-4 pt-6 pb-10">
            <div class="flex w-full max-w-md flex-col gap-6 rounded-xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8">
                <div v-if="heading || subheading" class="flex flex-col gap-1.5">
                    <h1 v-if="heading" class="font-display text-2xl font-bold tracking-tight">{{ heading }}</h1>
                    <p v-if="subheading" class="text-sm text-muted-foreground">{{ subheading }}</p>
                </div>

                <div>
                    <slot />
                </div>
            </div>

            <Link
                :href="home.url()"
                class="inline-flex min-h-11 items-center gap-1.5 rounded-md px-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-3.5" aria-hidden="true" />
                Back to the site
            </Link>
        </main>
    </div>
</template>
