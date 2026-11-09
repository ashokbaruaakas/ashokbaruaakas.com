<script setup lang="ts">
import AtsScoreCard from '@/components/app/ats/AtsScoreCard.vue'
import { formatDay } from '@/components/app/job-hunt/jobHunt'
import { portfolioSectionGroups } from '@/components/app/portfolio/sections'
import { Skeleton } from '@/components/ui/skeleton'
import { formatRelativeDate } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import { home, openSource, resume } from '@/routes'
import account from '@/routes/account'
import assistantRoutes from '@/routes/assistant'
import jobHuntRoutes from '@/routes/job-hunt'
import portfolioRoutes from '@/routes/portfolio'
import settingsRoutes from '@/routes/settings'
import type { AtsCheck } from '@/types/ats'
import type { PortfolioAvailability } from '@/types/data'
import type { JobHuntDashboardSummary } from '@/types/jobHunt'
import { Link } from '@inertiajs/vue3'
import { ArrowRight, BriefcaseBusiness, IdCard, Sparkles } from '@lucide/vue'
import { computed, ref } from 'vue'

type ContentFilter = 'All' | 'Filled' | 'Empty'

interface RecentConversation {
    id: string
    title: string
    updated_at: string | null
}

const props = defineProps<{
    availability?: PortfolioAvailability
    contentStatus?: Record<string, boolean>
    jobHunt?: JobHuntDashboardSummary | null
    recentConversations?: RecentConversation[]
    atsCheck?: AtsCheck | null
    twoFactorEnabled: boolean
    passkeyCount: number
    ai: { isReady: boolean; provider: string }
    mailEnabled: boolean
}>()

const filters: ContentFilter[] = ['All', 'Filled', 'Empty']
const selectedFilter = ref<ContentFilter>('All')

const allSections = computed(() =>
    portfolioSectionGroups.flatMap(({ label, items }) => items.map((item) => ({ ...item, group: label, isFilled: props.contentStatus?.[item.key] }))),
)

const sections = computed(() =>
    allSections.value.filter(
        ({ isFilled }) => selectedFilter.value === 'All' || isFilled === undefined || isFilled === (selectedFilter.value === 'Filled'),
    ),
)

const filledSectionCount = computed(() => allSections.value.filter(({ isFilled }) => isFilled).length)
const filledPercentage = computed(() => Math.round((filledSectionCount.value / allSections.value.length) * 100))

const publicPages = computed(() => [
    { title: 'Home', href: home.url(), isLive: props.availability?.hasHome },
    { title: 'Résumé', href: resume.url(), isLive: props.availability?.hasResume },
    { title: 'Open Source', href: openSource.url(), isLive: props.availability?.hasOpenSource },
])

const livePageCount = computed(() => publicPages.value.filter(({ isLive }) => isLive).length)

const huntStages = computed(() =>
    props.jobHunt
        ? [
              { label: 'To Apply', count: props.jobHunt.stages.to_apply },
              { label: 'In Progress', count: props.jobHunt.stages.in_progress },
              { label: 'Closed', count: props.jobHunt.stages.closed },
          ]
        : [],
)

const huntHighlights = computed(() => {
    if (!props.jobHunt) return []

    const { interviewing, offers, needs_follow_up } = props.jobHunt

    return [
        { label: interviewing === 1 ? '1 interviewing' : `${interviewing} interviewing`, isShown: interviewing > 0, tone: 'amber' },
        { label: offers === 1 ? '1 offer' : `${offers} offers`, isShown: offers > 0, tone: 'emerald' },
        { label: needs_follow_up === 1 ? '1 needs a follow-up' : `${needs_follow_up} need a follow-up`, isShown: needs_follow_up > 0, tone: 'blue' },
    ].filter(({ isShown }) => isShown)
})

const toneClasses: Record<string, string> = {
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
}

const setupRows = computed(() => [
    { label: 'Two-Factor', value: props.twoFactorEnabled ? 'On' : 'Off', needsAttention: !props.twoFactorEnabled, href: account.security.url() },
    {
        label: 'Passkeys',
        value: props.passkeyCount === 0 ? 'None yet' : String(props.passkeyCount),
        needsAttention: false,
        href: account.security.url(),
    },
    {
        label: 'AI Provider',
        value: props.ai.isReady ? props.ai.provider : 'No API key',
        needsAttention: !props.ai.isReady,
        href: settingsRoutes.ai.edit.url(),
    },
    { label: 'Email', value: props.mailEnabled ? 'On' : 'Off', needsAttention: false, href: settingsRoutes.mail.edit.url() },
])
</script>

<template>
    <div class="flex flex-col gap-10">
        <h1 class="sr-only">Dashboard</h1>

        <div class="grid gap-4 md:grid-cols-3">
            <section aria-labelledby="portfolio-card-title" class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <IdCard class="size-4 text-muted-foreground" aria-hidden="true" />
                    <h2 id="portfolio-card-title" class="text-sm font-semibold">Portfolio</h2>
                </div>

                <div v-if="contentStatus === undefined || availability === undefined" class="flex flex-col gap-3">
                    <Skeleton class="h-7 w-24" />
                    <Skeleton class="h-2 w-full" />
                    <Skeleton class="h-4 w-32" />
                </div>
                <div v-else class="flex flex-col gap-3">
                    <p class="text-sm text-muted-foreground">
                        <span class="font-display text-2xl font-bold text-foreground tabular-nums">{{ filledSectionCount }}</span>
                        / {{ allSections.length }} sections filled
                    </p>
                    <div
                        role="progressbar"
                        :aria-valuenow="filledPercentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Sections filled"
                        class="h-2 overflow-hidden rounded-full bg-muted"
                    >
                        <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: `${filledPercentage}%` }" />
                    </div>
                    <p class="text-sm text-muted-foreground">{{ livePageCount }} of {{ publicPages.length }} public pages live</p>
                </div>

                <Link
                    :href="portfolioRoutes.profile.edit.url()"
                    class="mt-auto inline-flex min-h-9 items-center gap-1.5 self-start text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                >
                    Edit Portfolio
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </Link>
            </section>

            <section aria-labelledby="job-hunt-card-title" class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <BriefcaseBusiness class="size-4 text-muted-foreground" aria-hidden="true" />
                    <h2 id="job-hunt-card-title" class="text-sm font-semibold">Job Hunt</h2>
                </div>

                <div v-if="jobHunt === undefined" class="flex flex-col gap-3">
                    <Skeleton class="h-4 w-40" />
                    <Skeleton class="h-12 w-full" />
                    <Skeleton class="h-4 w-28" />
                </div>
                <p v-else-if="jobHunt === null" class="text-sm text-muted-foreground">
                    No hunt is running. Start one to track the jobs you apply to.
                </p>
                <div v-else class="flex flex-col gap-3">
                    <div class="flex flex-col gap-0.5">
                        <p class="truncate font-medium">{{ jobHunt.title }}</p>
                        <p class="text-sm text-muted-foreground">Since {{ formatDay(jobHunt.started_on) }}</p>
                    </div>

                    <dl class="grid grid-cols-3 gap-2">
                        <div v-for="stage in huntStages" :key="stage.label" class="rounded-md bg-muted/60 px-2.5 py-2">
                            <dt class="text-xs text-muted-foreground">{{ stage.label }}</dt>
                            <dd class="font-display text-lg font-bold tabular-nums">{{ stage.count }}</dd>
                        </div>
                    </dl>

                    <ul v-if="huntHighlights.length > 0" class="flex flex-wrap gap-1.5">
                        <li
                            v-for="highlight in huntHighlights"
                            :key="highlight.label"
                            :class="cn('rounded-full px-2 py-0.5 text-xs font-medium', toneClasses[highlight.tone])"
                        >
                            {{ highlight.label }}
                        </li>
                    </ul>

                    <div v-if="jobHunt.upcoming.length > 0" class="flex flex-col gap-1">
                        <h3 class="text-xs font-medium text-muted-foreground">Coming Up</h3>
                        <ul class="flex flex-col">
                            <li v-for="item in jobHunt.upcoming" :key="`${item.id}-${item.label}-${item.date}`">
                                <Link
                                    :href="jobHuntRoutes.show.url(jobHunt.id, { query: { job: item.id } })"
                                    class="-mx-2 grid min-h-9 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 rounded-md px-2 text-sm hover:bg-accent"
                                >
                                    <span class="truncate">
                                        <span class="font-medium">{{ item.company }}</span>
                                        <span class="text-muted-foreground"> · {{ item.label }}</span>
                                    </span>
                                    <span class="text-muted-foreground tabular-nums">{{
                                        formatDay(item.date, { day: 'numeric', month: 'short' })
                                    }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <Link
                    :href="jobHunt ? jobHuntRoutes.show.url(jobHunt.id) : jobHuntRoutes.index.url()"
                    class="mt-auto inline-flex min-h-9 items-center gap-1.5 self-start text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                >
                    {{ jobHunt === null ? 'Start a Hunt' : 'Open Hunt' }}
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </Link>
            </section>

            <section aria-labelledby="assistant-card-title" class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <Sparkles class="size-4 text-muted-foreground" aria-hidden="true" />
                        <h2 id="assistant-card-title" class="text-sm font-semibold">Assistant</h2>
                    </div>
                    <span v-if="ai.isReady" class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                        <span aria-hidden="true" class="size-1.5 rounded-full bg-emerald-500" />
                        Ready
                    </span>
                </div>

                <p v-if="!ai.isReady" class="text-sm text-muted-foreground">
                    Add an API key for {{ ai.provider }} under
                    <Link :href="settingsRoutes.ai.edit.url()" class="font-medium text-foreground underline-offset-4 hover:underline"
                        >AI Settings</Link
                    >
                    to start chatting.
                </p>

                <div v-if="recentConversations === undefined" class="flex flex-col gap-2">
                    <Skeleton v-for="index in 3" :key="index" class="h-5 w-full" />
                </div>
                <p v-else-if="recentConversations.length === 0 && ai.isReady" class="text-sm text-muted-foreground">
                    No conversations yet. Ask for a cover letter, a résumé review or help with an application.
                </p>
                <div v-else-if="recentConversations.length > 0" class="flex flex-col gap-1">
                    <h3 class="text-xs font-medium text-muted-foreground">Recent</h3>
                    <ul class="flex flex-col">
                        <li v-for="conversation in recentConversations" :key="conversation.id">
                            <Link
                                :href="assistantRoutes.show.url(conversation.id)"
                                class="-mx-2 grid min-h-9 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 rounded-md px-2 text-sm hover:bg-accent"
                            >
                                <span class="truncate font-medium">{{ conversation.title }}</span>
                                <span v-if="conversation.updated_at" class="text-xs text-muted-foreground">
                                    {{ formatRelativeDate(conversation.updated_at) }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <Link
                    :href="assistantRoutes.show.url()"
                    class="mt-auto inline-flex min-h-9 items-center gap-1.5 self-start text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                >
                    New Conversation
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </Link>
            </section>
        </div>

        <AtsScoreCard :check="atsCheck" :can-match-job="ai.isReady" />

        <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16">
            <section class="flex min-w-0 flex-1 flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="font-display text-xl font-bold tracking-tight">Portfolio Content</h2>
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
                                <span v-else class="text-right font-medium text-emerald-700 dark:text-emerald-400">Add Content</span>
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
                    <h2 id="public-pages-title" class="pb-2 text-sm font-semibold">Public Pages</h2>
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
                            <span v-else class="text-muted-foreground">Not Live</span>
                        </div>
                    </template>
                    <p class="pt-2 text-sm text-muted-foreground">Changes can take up to 5 minutes to show.</p>
                </section>

                <div aria-hidden="true" class="h-px bg-border" />

                <section aria-labelledby="setup-title" class="flex flex-col gap-1">
                    <h2 id="setup-title" class="pb-2 text-sm font-semibold">Account and Setup</h2>
                    <Link
                        v-for="row in setupRows"
                        :key="row.label"
                        :href="row.href"
                        class="-mx-2 flex min-h-9 items-center justify-between gap-3 rounded-md px-2 text-sm hover:bg-accent"
                    >
                        <span class="text-muted-foreground">{{ row.label }}</span>
                        <span :class="cn(row.needsAttention && 'inline-flex items-center gap-1.5 font-medium text-amber-700 dark:text-amber-400')">
                            <span v-if="row.needsAttention" aria-hidden="true" class="size-1.5 rounded-full bg-amber-500" />
                            {{ row.value }}
                        </span>
                    </Link>
                    <p v-if="!mailEnabled" class="pt-2 text-sm text-muted-foreground">
                        Email is off, so there is no password reset link. Locked out? Run
                        <code class="text-xs">php artisan owner:reset-password</code>.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
