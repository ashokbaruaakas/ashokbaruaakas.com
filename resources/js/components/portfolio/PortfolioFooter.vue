<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUp, ArrowUpRight, Clock, Download } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    getScrollBehavior,
    goToHomeSection,
    homeSectionUrl,
} from '@/composables/useScrollSpy';
import { openSource, resume } from '@/routes';
import type { Portfolio } from '@/types/portfolio';

const props = withDefaults(
    defineProps<{
        portfolio: Portfolio;
        reserveMobileNavSpace?: boolean;
    }>(),
    { reserveMobileNavSpace: false },
);

const timeZone = 'Asia/Dhaka';
const sourceUrl = 'https://github.com/ashokbaruaakas/ashokbaruaakas.com';
const resumePdfUrl = '/Ashok-Barua-Resume.pdf';

const year = computed(() => new Date().getFullYear());
const role = computed(() => props.portfolio.tagline.split(' — ')[0]);
const city = computed(() => props.portfolio.location.split(',')[0]);

const lastUpdated = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    year: 'numeric',
    timeZone,
}).format(new Date(__BUILD_DATE__));

const exploreLinks = [
    { label: 'Work', sectionId: 'work' },
    { label: 'Case studies', sectionId: 'case-studies' },
] as const;

const contactLinks = computed(() =>
    props.portfolio.socialLinks
        .filter(({ isPrimary }) => isPrimary)
        .map(({ platform, url }) => ({ label: platform, url })),
);

/** Rendered only after mount so the server and client markup always match. */
const localTime = ref<string | null>(null);
let clockTimer: ReturnType<typeof setInterval> | undefined;

function updateLocalTime(): void {
    localTime.value = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone,
    }).format(new Date());
}

onMounted(() => {
    updateLocalTime();
    clockTimer = setInterval(updateLocalTime, 30_000);
});

onBeforeUnmount(() => clearInterval(clockTimer));

function scrollToTop(): void {
    window.scrollTo({ top: 0, behavior: getScrollBehavior() });
}
</script>

<template>
    <footer class="relative border-t border-emerald-500/10">
        <div
            class="mx-auto max-w-5xl px-6 pt-12 pb-8 text-sm"
            :class="{ 'pb-28 lg:pb-8': reserveMobileNavSpace }"
        >
            <div
                class="grid gap-10 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <img
                            src="/images/ashok-barua-avatar.webp"
                            alt=""
                            width="40"
                            height="40"
                            loading="lazy"
                            class="size-10 rounded-full ring-1 ring-emerald-500/20"
                        />
                        <div>
                            <p
                                class="bg-gradient-to-r from-emerald-700 to-teal-700 bg-clip-text font-display text-base font-semibold text-transparent dark:from-emerald-400 dark:to-teal-300"
                            >
                                {{ portfolio.name }}
                            </p>
                            <p class="text-muted-foreground">{{ role }}</p>
                        </div>
                    </div>

                    <ul class="mt-5 space-y-2 text-muted-foreground">
                        <li
                            v-if="portfolio.availability"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="inline-flex size-4 items-center justify-center"
                                aria-hidden="true"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-emerald-500"
                                />
                            </span>
                            {{ portfolio.availability }}
                        </li>
                        <li class="flex items-center gap-2">
                            <Clock
                                class="size-4 text-emerald-600 dark:text-emerald-400"
                                aria-hidden="true"
                            />
                            <span>
                                <template v-if="localTime">
                                    {{ localTime }} in
                                </template>
                                {{ city }}
                                <span class="text-muted-foreground/70">
                                    (UTC+6)
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <nav aria-labelledby="footer-explore">
                        <h2
                            id="footer-explore"
                            class="text-xs font-medium tracking-widest text-emerald-700 uppercase dark:text-emerald-400"
                        >
                            Explore
                        </h2>
                        <ul class="mt-4 space-y-2.5">
                            <li
                                v-for="link in exploreLinks"
                                :key="link.sectionId"
                            >
                                <a
                                    :href="homeSectionUrl(link.sectionId)"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                    @click="
                                        goToHomeSection($event, link.sectionId)
                                    "
                                >
                                    {{ link.label }}
                                </a>
                            </li>
                            <li>
                                <Link
                                    :href="resume.url()"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Résumé
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="openSource.url()"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Open source
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <div>
                        <h2
                            class="text-xs font-medium tracking-widest text-emerald-700 uppercase dark:text-emerald-400"
                        >
                            Contact
                        </h2>
                        <ul class="mt-4 space-y-2.5">
                            <li>
                                <a
                                    :href="`mailto:${portfolio.email}`"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Email
                                </a>
                            </li>
                            <li v-for="link in contactLinks" :key="link.url">
                                <a
                                    :href="link.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group inline-flex items-center gap-1 text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {{ link.label }}
                                    <ArrowUpRight
                                        class="size-3.5 opacity-60 transition-opacity group-hover:opacity-100"
                                        aria-hidden="true"
                                    />
                                    <span class="sr-only">
                                        (opens in a new tab)
                                    </span>
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="resumePdfUrl"
                                    download
                                    class="group inline-flex items-center gap-1 text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Résumé PDF
                                    <Download
                                        class="size-3.5 opacity-60 transition-opacity group-hover:opacity-100"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-4 border-t border-border/60 pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    &copy; {{ year }} {{ portfolio.name }}
                    <span class="px-1 text-border">·</span>
                    Updated {{ lastUpdated }}
                </p>

                <div class="flex items-center justify-between gap-4">
                    <p>
                        Built with
                        <span class="font-mono">Laravel · Inertia · Vue</span>
                        <span class="px-1 text-border">·</span>
                        <a
                            :href="sourceUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-0.5 text-emerald-700 transition-colors hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >
                            View source
                            <ArrowUpRight class="size-3" aria-hidden="true" />
                            <span class="sr-only">(opens in a new tab)</span>
                        </a>
                    </p>

                    <button
                        type="button"
                        aria-label="Back to top"
                        title="Back to top"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-border bg-background/50 text-muted-foreground backdrop-blur transition-colors hover:border-emerald-500/40 hover:text-emerald-600"
                        @click="scrollToTop"
                    >
                        <ArrowUp class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </footer>
</template>
