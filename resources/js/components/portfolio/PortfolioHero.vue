<script setup lang="ts">
import { ArrowDown, ArrowUpRight, MapPin } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useScrollSpy } from '@/composables/useScrollSpy';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const { scrollTo } = useScrollSpy();

const codeWords = [
    'const',
    'await',
    'artisan',
    'eloquent',
    'migrate',
    'dispatch',
    'resolve',
    'schedule',
    'boot',
    'queue',
    'listen',
    'compile',
];

const codeLine = [...codeWords, ...codeWords].join(' · ') + ' ·';

const taglineParts = computed(() => {
    const [rolePart, ...stackParts] = props.portfolio.tagline.split(' · ');

    return { role: rolePart, stack: stackParts.join(' · ') };
});

const fullName = computed(() => props.portfolio.name);

const typedText = ref('');
const typingDone = ref(false);

const sparkles = [
    { top: '-30%', left: '20%', delay: '0s' },
    { top: '-20%', left: '98%', delay: '0.3s' },
    { top: '0%', left: '-6%', delay: '0.6s' },
    { top: '15%', left: '50%', delay: '0.9s' },
    { top: '40%', left: '102%', delay: '1.2s' },
    { top: '55%', left: '-8%', delay: '1.5s' },
    { top: '70%', left: '35%', delay: '1.8s' },
    { top: '80%', left: '105%', delay: '2.1s' },
    { top: '90%', left: '10%', delay: '2.4s' },
    { top: '100%', left: '65%', delay: '2.7s' },
] as const;

let typeTimer: ReturnType<typeof setInterval> | undefined;

function startTypewriter() {
    let index = 0;

    typeTimer = setInterval(() => {
        index += 1;
        typedText.value = fullName.value.slice(0, index);

        if (index >= fullName.value.length) {
            clearInterval(typeTimer);
            typingDone.value = true;
        }
    }, 75);
}

onMounted(() => {
    const startTimer = setTimeout(startTypewriter, 600);

    onBeforeUnmount(() => {
        clearTimeout(startTimer);
        clearInterval(typeTimer);
    });
});
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
                    {{ portfolio.location }} · UTC +06:00
                </span>
            </p>

            <h1
                class="font-display text-5xl font-bold tracking-tight text-balance sm:text-6xl lg:text-8xl"
            >
                <span
                    class="relative inline-block bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-400 bg-clip-text text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
                >
                    {{ typedText }}
                    <span
                        v-if="!typingDone"
                        class="ml-0.5 inline-block h-[0.8em] w-[0.08em] translate-y-[0.08em] rounded-sm bg-emerald-400 motion-safe:animate-[blinkCursor_1s_step-end_infinite]"
                    />
                    <template v-if="typingDone">
                        <span
                            v-for="(sparkle, index) in sparkles"
                            :key="index"
                            class="pointer-events-none absolute text-sm text-emerald-400/80 select-none motion-safe:animate-[sparklePing_2.5s_ease-in-out_infinite]"
                            :style="{
                                top: sparkle.top,
                                left: sparkle.left,
                                animationDelay: sparkle.delay,
                            }"
                        >
                            ✦
                        </span>
                    </template>
                </span>
            </h1>

            <p class="mt-4 text-xl font-medium text-foreground/90 lg:text-2xl">
                {{ taglineParts.role }}
            </p>

            <p
                class="mt-2 font-mono text-sm tracking-wide text-emerald-600 dark:text-emerald-400"
            >
                {{ taglineParts.stack }}
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
        </div>

        <div
            aria-hidden="true"
            class="pointer-events-none absolute right-0 bottom-0 left-0 overflow-hidden select-none"
        >
            <p
                class="font-mono text-[100px] leading-none tracking-tight whitespace-nowrap opacity-[0.04] motion-safe:animate-[scrollCode_50s_linear_infinite] lg:text-[160px] dark:opacity-[0.03]"
            >
                {{ codeLine }}
            </p>
        </div>
    </section>
</template>
