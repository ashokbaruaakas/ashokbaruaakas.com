<script setup lang="ts">
import { ArrowDown, ArrowUpRight, MapPin } from '@lucide/vue';
import { useScrollSpy } from '@/composables/useScrollSpy';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();

const { scrollTo } = useScrollSpy();
</script>

<template>
    <section
        id="hero"
        class="flex min-h-screen flex-col justify-center px-6 pt-16"
    >
        <div class="mx-auto w-full max-w-5xl">
            <p
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-600/20 bg-emerald-600/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
            >
                <span class="relative flex size-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    />
                    <span
                        class="relative inline-flex size-2 rounded-full bg-emerald-500"
                    />
                </span>
                {{ portfolio.location }}
            </p>

            <h1
                class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl"
            >
                Ashok Barua Akas
            </h1>

            <p class="mt-4 text-lg text-muted-foreground sm:text-xl">
                {{ portfolio.tagline }}
            </p>

            <p class="mt-6 max-w-2xl text-base text-muted-foreground">
                {{ portfolio.bio }}
            </p>

            <div class="mt-10 flex flex-wrap items-center gap-4">
                <a
                    href="#projects"
                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
                    @click.prevent="scrollTo('projects')"
                >
                    View projects
                    <ArrowDown class="size-4" />
                </a>
                <a
                    href="#connect"
                    class="inline-flex items-center gap-2 rounded-md border border-border px-5 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                    @click.prevent="scrollTo('connect')"
                >
                    Get in touch
                    <ArrowUpRight class="size-4" />
                </a>
            </div>

            <p
                class="mt-12 flex items-center gap-1.5 text-sm text-muted-foreground"
            >
                <MapPin class="size-4" />
                Chittagong, Bangladesh · UTC +06:00
            </p>
        </div>
    </section>
</template>
