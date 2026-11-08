<script setup lang="ts">
import { BrandHeader, TerminalCard } from '@/components/layout'
import PortfolioBackground from '@/components/portfolio/PortfolioBackground.vue'
import PortfolioKeywordMarquee from '@/components/portfolio/PortfolioKeywordMarquee.vue'
import { Button } from '@/components/ui/button'
import { dashboard } from '@/routes'
import { Head, Link } from '@inertiajs/vue3'
import { Check, Circle } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps<{
    hasOwner: boolean
}>()

const creatorUrl = 'https://github.com/ashokbaruaakas'
const repositoryUrl = `${creatorUrl}/ashokbaruaakas.com`
const setupGuideUrl = `${repositoryUrl}#create-your-account`
const marqueeWords = ['hello world', 'make it yours', 'php artisan owner:create', 'first commit']

const steps = computed(() => [
    { label: 'Install the app', isDone: true },
    { label: 'Create your account', isDone: props.hasOwner, command: props.hasOwner ? undefined : 'php artisan owner:create' },
    { label: 'Add your story at /portfolio', isDone: false },
])
</script>

<template>
    <div data-surface="app" class="relative isolate flex min-h-svh flex-col overflow-clip bg-sidebar text-foreground antialiased">
        <Head title="Welcome">
            <meta head-key="robots" name="robots" content="noindex" />
        </Head>

        <PortfolioBackground />
        <BrandHeader />

        <main class="mx-auto grid w-full max-w-5xl flex-1 content-center items-center gap-10 px-6 pt-6 pb-16 lg:grid-cols-2 lg:gap-16">
            <div class="flex flex-col items-start">
                <h1 class="flex items-end gap-2 font-display text-6xl leading-none font-bold tracking-tighter sm:text-7xl">
                    <span
                        class="bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-700 bg-clip-text pb-[0.1em] text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
                    >
                        Hello, world
                    </span>
                    <span
                        aria-hidden="true"
                        class="mb-[0.22em] h-[0.1em] w-[0.42em] rounded-sm bg-emerald-500 motion-safe:animate-[blinkCursor_1.1s_steps(1)_infinite]"
                    />
                </h1>

                <template v-if="hasOwner">
                    <p class="mt-6 font-display text-2xl font-bold tracking-tight text-balance sm:text-3xl">This portfolio is about to begin</p>
                    <p class="mt-3 text-lg text-pretty text-muted-foreground">
                        Its owner is still writing. Add your story from the dashboard, and this page becomes your portfolio.
                    </p>

                    <Button class="mt-8" as-child>
                        <Link :href="dashboard()">Go to Dashboard</Link>
                    </Button>
                </template>

                <template v-else>
                    <p class="mt-6 font-display text-2xl font-bold tracking-tight text-balance sm:text-3xl">This portfolio needs an owner</p>
                    <p class="mt-3 text-lg text-pretty text-muted-foreground">
                        The site is installed. Follow the developer instructions to create your account, then sign in and make it yours.
                    </p>

                    <Button class="mt-8" as-child>
                        <a :href="setupGuideUrl" target="_blank" rel="noopener">Read the instructions</a>
                    </Button>
                </template>

                <p class="mt-8 text-sm text-muted-foreground">
                    Crafted by
                    <a :href="creatorUrl" target="_blank" rel="noopener" class="font-medium text-foreground underline-offset-4 hover:underline">
                        Ashok Barua
                    </a>
                    ·
                    <a :href="repositoryUrl" target="_blank" rel="noopener" class="underline-offset-4 hover:text-foreground hover:underline">
                        View the source
                    </a>
                </p>
            </div>

            <TerminalCard>
                <ol class="flex flex-col gap-3">
                    <li v-for="step in steps" :key="step.label" class="flex items-start gap-3">
                        <Check v-if="step.isDone" class="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-400" />
                        <Circle v-else class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                        <span class="flex min-w-0 flex-col gap-1">
                            <span :class="step.isDone ? 'text-muted-foreground' : ''">{{ step.label }}</span>
                            <code v-if="step.command" class="break-all">
                                <span class="text-emerald-700 dark:text-emerald-400">$</span>
                                {{ step.command }}
                                <span
                                    aria-hidden="true"
                                    class="ml-0.5 inline-block h-4 w-2 translate-y-0.5 bg-foreground motion-safe:animate-[blinkCursor_1.1s_steps(1)_infinite]"
                                />
                            </code>
                        </span>
                    </li>
                </ol>
            </TerminalCard>
        </main>

        <PortfolioKeywordMarquee :words="marqueeWords" />
    </div>
</template>
