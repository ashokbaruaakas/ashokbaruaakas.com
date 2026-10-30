<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowUpRight, FileText } from '@lucide/vue';
import { computed } from 'vue';
import { useScrollSpy } from '@/composables/useScrollSpy';
import { resume } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const { scrollTo } = useScrollSpy();

const bioHtml = computed(() => {
    return props.portfolio.bio
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
});

const highlights = computed(() => [
    { label: 'Experience', value: '7+ yrs', detail: 'Shipping to production' },
    { label: 'Domain', value: 'Payments', detail: 'SiPay · HSM · card saving' },
    {
        label: 'Timezone',
        value: 'UTC+6',
        detail: props.portfolio.location.split(',')[0],
    },
]);
</script>

<template>
    <section
        id="hero"
        class="relative flex flex-col overflow-hidden px-6 pt-28 pb-16 sm:min-h-screen sm:justify-center sm:pt-16 sm:pb-0"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.06),transparent_70%)]"
        />

        <div
            class="mx-auto grid w-full max-w-5xl items-center gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-12"
        >
            <div
                class="relative size-56 justify-self-center sm:size-64 lg:order-last lg:size-96"
            >
                <div
                    aria-hidden="true"
                    class="absolute -inset-8 rounded-full bg-[radial-gradient(circle,rgba(16,185,129,0.25),transparent_70%)] blur-2xl dark:bg-[radial-gradient(circle,rgba(16,185,129,0.3),transparent_70%)]"
                />
                <div
                    aria-hidden="true"
                    class="absolute -inset-3 rounded-full border border-dashed border-emerald-600/25 motion-safe:animate-[spin_60s_linear_infinite] dark:border-emerald-400/20"
                />
                <div
                    aria-hidden="true"
                    class="absolute inset-0 overflow-hidden rounded-full bg-gradient-to-b from-emerald-100 to-teal-50 ring-1 ring-emerald-600/15 dark:from-emerald-500/25 dark:via-teal-500/10 dark:to-[#0b1220] dark:ring-emerald-400/20"
                >
                    <div
                        class="absolute inset-0 bg-[linear-gradient(to_right,rgba(16,185,129,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(16,185,129,0.12)_1px,transparent_1px)] bg-[size:24px_24px]"
                    />
                </div>
                <div
                    class="portrait-mask absolute inset-x-0 bottom-0 h-[125%] overflow-hidden"
                >
                    <img
                        src="/images/ashok-barua-portrait-640.webp"
                        srcset="
                            /images/ashok-barua-portrait-640.webp 640w,
                            /images/ashok-barua-portrait-960.webp 960w
                        "
                        sizes="(min-width: 1024px) 480px, (min-width: 640px) 320px, 280px"
                        :alt="`Portrait of ${portfolio.name}`"
                        width="960"
                        height="960"
                        fetchpriority="high"
                        class="absolute bottom-0 left-1/2 w-[125%] max-w-none -translate-x-1/2"
                    />
                </div>
            </div>

            <div class="min-w-0 break-words">
                <p
                    v-if="portfolio.availability"
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-600/20 bg-emerald-600/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                >
                    <span class="relative flex size-1.5" aria-hidden="true">
                        <span
                            class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping"
                        />
                        <span
                            class="relative inline-flex size-1.5 rounded-full bg-emerald-500"
                        />
                    </span>
                    {{ portfolio.availability }}
                </p>

                <h1
                    class="bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-700 bg-clip-text pb-1 font-display text-5xl font-bold tracking-tight text-balance text-transparent sm:text-6xl lg:text-7xl dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
                >
                    {{ portfolio.name }}
                </h1>

                <p
                    class="mt-3 text-xl font-medium text-balance text-foreground/90 lg:text-2xl"
                >
                    {{ portfolio.tagline }}
                </p>

                <p
                    class="mt-4 max-w-xl text-base text-muted-foreground"
                    v-html="bioHtml"
                ></p>

                <dl
                    class="mt-8 grid max-w-xl grid-cols-3 divide-x divide-border border-y border-border"
                >
                    <div
                        v-for="highlight in highlights"
                        :key="highlight.label"
                        class="flex min-w-0 flex-col gap-0.5 py-3 pr-2 sm:pr-3 [&:not(:first-child)]:pl-3 sm:[&:not(:first-child)]:pl-4"
                    >
                        <dt class="sr-only">{{ highlight.label }}</dt>
                        <dd
                            class="font-display text-base font-semibold tracking-tight whitespace-nowrap sm:text-xl"
                        >
                            {{ highlight.value }}
                        </dd>
                        <dd class="text-xs text-muted-foreground">
                            {{ highlight.detail }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a
                        href="#work"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-emerald-800"
                        @click.prevent="scrollTo('work')"
                    >
                        View Work
                        <ArrowDown class="size-4" aria-hidden="true" />
                    </a>
                    <Link
                        :href="resume.url()"
                        class="inline-flex items-center gap-2 rounded-md border border-border px-5 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                    >
                        View Résumé
                        <FileText class="size-4" aria-hidden="true" />
                    </Link>
                    <a
                        href="#connect"
                        class="inline-flex items-center gap-1.5 px-2 py-2.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        @click.prevent="scrollTo('connect')"
                    >
                        Get in touch
                        <ArrowUpRight class="size-4" aria-hidden="true" />
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
