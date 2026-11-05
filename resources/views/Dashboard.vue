<script setup lang="ts">
import { portfolioSectionGroups } from '@/components/app/portfolio/sections'
import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'
import { home, openSource, resume } from '@/routes'
import account from '@/routes/account'
import type { PortfolioAvailability } from '@/types/data'
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from '@lucide/vue'
import { computed, ref } from 'vue'

type ContentFilter = 'All' | 'Filled' | 'Empty'

const props = defineProps<{
    availability?: PortfolioAvailability
    contentStatus?: Record<string, boolean>
    twoFactorEnabled: boolean
    passkeyCount: number
}>()

const filters: ContentFilter[] = ['All', 'Filled', 'Empty']
const selectedFilter = ref<ContentFilter>('All')

const sections = computed(() =>
    portfolioSectionGroups
        .flatMap(({ label, items }) => items.map((item) => ({ ...item, group: label, isFilled: props.contentStatus?.[item.key] })))
        .filter(({ isFilled }) => selectedFilter.value === 'All' || isFilled === undefined || isFilled === (selectedFilter.value === 'Filled')),
)

const publicPages = computed(() => [
    { title: 'Home', href: home.url(), isLive: props.availability?.hasProfile },
    { title: 'Résumé', href: resume.url(), isLive: props.availability?.hasResume },
    { title: 'Open source', href: openSource.url(), isLive: props.availability?.hasOpenSource },
])
</script>

<template>
    <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16">
        <section class="flex min-w-0 flex-1 flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="font-display text-xl font-bold tracking-tight">Portfolio content</h1>
                <p class="text-sm text-muted-foreground">What visitors can see, and what still needs content.</p>
            </div>

            <div class="flex self-start rounded-lg bg-muted p-1">
                <button
                    v-for="filter in filters"
                    :key="filter"
                    type="button"
                    :aria-pressed="selectedFilter === filter"
                    :class="
                        cn(
                            'min-h-8 cursor-pointer rounded-md border px-3 text-sm font-medium transition-colors',
                            selectedFilter === filter
                                ? 'border-border bg-background text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="selectedFilter = filter"
                >
                    {{ filter }}
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <div
                    aria-hidden="true"
                    class="grid grid-cols-[minmax(0,1fr)_7rem] gap-3 border-b px-4 py-2 text-sm text-muted-foreground sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_7rem]"
                >
                    <span>Section</span>
                    <span class="hidden sm:block">Group</span>
                    <span class="text-right">Status</span>
                </div>

                <ul class="divide-y" :aria-busy="contentStatus === undefined">
                    <li v-for="section in sections" :key="section.key">
                        <Link
                            :href="section.href"
                            class="grid min-h-11 grid-cols-[minmax(0,1fr)_7rem] items-center gap-3 px-4 text-sm transition-colors hover:bg-accent sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_7rem]"
                        >
                            <span class="font-medium">{{ section.title }}</span>
                            <span class="hidden text-muted-foreground sm:block">{{ section.group }}</span>
                            <Skeleton v-if="section.isFilled === undefined" class="ml-auto h-3.5 w-12" />
                            <span v-else-if="section.isFilled" class="text-right text-muted-foreground">Filled</span>
                            <span v-else class="text-right font-medium text-emerald-700 dark:text-emerald-400">Add content</span>
                        </Link>
                    </li>
                    <li v-if="sections.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                        {{ selectedFilter === 'Empty' ? 'Every section has content.' : 'No sections have content yet.' }}
                    </li>
                </ul>
            </div>
        </section>

        <aside class="flex flex-col gap-6 lg:w-64 lg:shrink-0">
            <section aria-labelledby="public-pages-title" class="flex flex-col gap-1">
                <h2 id="public-pages-title" class="pb-2 text-sm font-semibold">Public pages</h2>
                <template v-for="publicPage in publicPages" :key="publicPage.title">
                    <div class="flex min-h-9 items-center justify-between gap-3 text-sm">
                        <Link v-if="publicPage.isLive" :href="publicPage.href" class="hover:underline">{{ publicPage.title }}</Link>
                        <span v-else>{{ publicPage.title }}</span>

                        <Skeleton v-if="publicPage.isLive === undefined" class="h-3.5 w-10" />
                        <span
                            v-else-if="publicPage.isLive"
                            class="inline-flex items-center gap-1.5 font-medium text-emerald-700 dark:text-emerald-400"
                        >
                            <span aria-hidden="true" class="size-1.5 rounded-full bg-emerald-500" />
                            Live
                        </span>
                        <span v-else class="text-muted-foreground">Not live</span>
                    </div>
                </template>
                <p class="pt-2 text-sm text-muted-foreground">Changes can take up to 5 minutes to show.</p>
            </section>

            <div aria-hidden="true" class="h-px bg-border" />

            <section aria-labelledby="security-title" class="flex flex-col gap-1">
                <h2 id="security-title" class="pb-2 text-sm font-semibold">Account security</h2>
                <div class="flex min-h-9 items-center justify-between gap-3 text-sm">
                    <span class="text-muted-foreground">Two-factor</span>
                    <span>{{ twoFactorEnabled ? 'On' : 'Off' }}</span>
                </div>
                <div class="flex min-h-9 items-center justify-between gap-3 text-sm">
                    <span class="text-muted-foreground">Passkeys</span>
                    <span>{{ passkeyCount === 0 ? 'None yet' : passkeyCount }}</span>
                </div>
                <Link
                    :href="account.security.url()"
                    class="inline-flex min-h-9 items-center gap-1.5 self-start text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                >
                    Manage security
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </Link>
            </section>
        </aside>
    </div>
</template>
