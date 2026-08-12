<script setup lang="ts">
import { ArrowDown, ArrowUpRight, MapPin } from '@lucide/vue';
import { useScrollSpy } from '@/composables/useScrollSpy';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();

const { scrollTo } = useScrollSpy();

const codeWords = [
    'const',
    'await',
    'build',
    'ship',
    'deploy',
    'iterate',
    'type',
    'mount',
    'commit',
    'push',
    'render',
    'compile',
];
</script>

<template>
    <section
        id="hero"
        class="relative flex min-h-screen flex-col justify-center overflow-hidden px-6 pt-16"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.06),transparent_70%)]"
        />

        <div class="mx-auto w-full max-w-5xl">
            <p
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-600/20 bg-emerald-600/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
            >
                <MapPin class="size-3.5 text-emerald-500" />
                <span
                    class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent dark:from-emerald-400 dark:to-teal-300"
                >
                    {{ portfolio.location }}
                </span>
            </p>

            <h1
                class="font-display text-5xl font-bold tracking-tight text-balance sm:text-6xl lg:text-8xl"
            >
                <span
                    class="bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-400 bg-clip-text text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
                >
                    Ashok Barua Akas
                </span>
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

        <div
            aria-hidden="true"
            class="pointer-events-none absolute right-0 bottom-0 left-0 overflow-hidden select-none"
        >
            <p
                class="font-mono text-[100px] leading-none tracking-tight whitespace-nowrap opacity-[0.04] lg:text-[160px] dark:opacity-[0.03]"
            >
                {{ codeWords.join(' · ') }} ·
            </p>
        </div>
    </section>
</template>
