<script setup lang="ts">
import { BrandHeader } from '@/components/layout'
import PortfolioBackground from '@/components/portfolio/PortfolioBackground.vue'
import PortfolioKeywordMarquee from '@/components/portfolio/PortfolioKeywordMarquee.vue'
import { Button } from '@/components/ui/button'
import { useIsSignedIn } from '@/composables/useIsSignedIn'
import { dashboard, home } from '@/routes'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
    status: 403 | 404 | 419 | 500 | 503
    supportEmail?: string | null
}>()

const page = usePage()
const isSignedIn = useIsSignedIn()
const path = computed(() => page.url.split('?')[0])

const messages = computed(() => ({
    403: {
        title: 'This one is private',
        description: "You don't have permission to open this page.",
        marquee: 'permission denied',
        command: `cat ${path.value}`,
        output: `cat: ${path.value}: Permission denied`,
        hint: "# sudo won't help here",
    },
    404: {
        title: 'Nothing lives here',
        description: 'The page may have moved, or the link may have a typo.',
        marquee: 'not found',
        command: `cd ${path.value}`,
        output: `cd: no such file or directory: ${path.value}`,
        hint: '# cd ~ gets you home',
    },
    419: {
        title: 'This page timed out',
        description: 'Your session expired while the page was open. Refresh it and try again.',
        marquee: 'session expired',
        command: 'session --status',
        output: 'session: expired',
        hint: '# refresh to start a new one',
    },
    500: {
        title: 'Something broke on this end',
        description: "It isn't anything you did. Give it a moment and try again.",
        marquee: 'server error',
        command: `curl ${path.value}`,
        output: 'curl: (52) Empty reply from server',
        hint: '# not your fault, try again shortly',
    },
    503: {
        title: 'Down for a quick tune-up',
        description: 'The site is getting some maintenance. Check back soon.',
        marquee: 'back soon',
        command: 'uptime',
        output: 'uptime: down for maintenance',
        hint: '# back online soon',
    },
}))

const message = computed(() => messages.value[props.status])
const marqueeWords = computed(() => Array.from({ length: 4 }, () => `${props.status} ${message.value.marquee}`))
const retryLabels: Partial<Record<number, string>> = { 419: 'Refresh page', 500: 'Try again', 503: 'Try again' }
const retryLabel = computed(() => retryLabels[props.status])

const refresh = () => window.location.reload()
</script>

<template>
    <div data-surface="app" class="relative isolate flex min-h-svh flex-col overflow-clip bg-sidebar text-foreground antialiased">
        <Head :title="message.title" />

        <PortfolioBackground />
        <BrandHeader />

        <main class="mx-auto grid w-full max-w-5xl flex-1 content-center items-center gap-10 px-6 pt-6 pb-16 lg:grid-cols-2 lg:gap-16">
            <div class="flex flex-col items-start">
                <p class="flex items-end gap-3 font-display text-8xl leading-none font-bold tracking-tighter sm:text-9xl">
                    <span
                        class="bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-700 bg-clip-text text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
                    >
                        {{ status }}
                    </span>
                    <span
                        aria-hidden="true"
                        class="mb-[0.14em] h-[0.1em] w-[0.42em] rounded-sm bg-emerald-500 motion-safe:animate-[blinkCursor_1.1s_steps(1)_infinite]"
                    />
                </p>

                <h1 class="mt-6 font-display text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ message.title }}</h1>
                <p class="mt-3 text-lg text-pretty text-muted-foreground">{{ message.description }}</p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Button v-if="retryLabel" @click="refresh">{{ retryLabel }}</Button>
                    <Button :variant="retryLabel ? 'outline' : 'default'" as-child>
                        <Link :href="home()">Back to the site</Link>
                    </Button>
                    <Button v-if="isSignedIn" variant="outline" as-child>
                        <Link :href="dashboard()">Go to dashboard</Link>
                    </Button>
                </div>

                <p v-if="supportEmail" class="mt-6 text-sm text-muted-foreground">
                    Still stuck? Email
                    <a :href="`mailto:${supportEmail}`" class="font-medium text-foreground underline-offset-4 hover:underline">{{ supportEmail }}</a>
                </p>
            </div>

            <div aria-hidden="true" class="min-w-0 overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm">
                <div class="flex items-center gap-1.5 border-b px-4 py-3">
                    <span class="size-2.5 rounded-full bg-border" />
                    <span class="size-2.5 rounded-full bg-border" />
                    <span class="size-2.5 rounded-full bg-emerald-500" />
                    <span class="ml-2 font-mono text-xs text-muted-foreground">~/portfolio</span>
                </div>

                <div class="flex flex-col gap-2 p-4 font-mono text-sm leading-relaxed">
                    <p class="line-clamp-3 break-all"><span class="text-emerald-700 dark:text-emerald-400">$</span> {{ message.command }}</p>
                    <p class="line-clamp-3 break-all text-muted-foreground">{{ message.output }}</p>
                    <p class="text-emerald-700 dark:text-emerald-400">{{ message.hint }}</p>
                    <p class="flex items-center gap-2">
                        <span class="text-emerald-700 dark:text-emerald-400">$</span>
                        <span class="h-4 w-2 bg-foreground motion-safe:animate-[blinkCursor_1.1s_steps(1)_infinite]" />
                    </p>
                </div>
            </div>
        </main>

        <PortfolioKeywordMarquee :words="marqueeWords" />
    </div>
</template>
