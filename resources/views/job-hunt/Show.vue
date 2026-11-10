<script setup lang="ts">
import ApplicationSheet from '@/components/app/job-hunt/ApplicationSheet.vue'
import EndHuntDialog from '@/components/app/job-hunt/EndHuntDialog.vue'
import HuntFields from '@/components/app/job-hunt/HuntFields.vue'
import { chanceClass, daysBetween, formatDay } from '@/components/app/job-hunt/jobHunt'
import StatusBadge from '@/components/app/job-hunt/StatusBadge.vue'
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { cn } from '@/lib/utils'
import jobHuntRoutes from '@/routes/job-hunt'
import type {
    JobApplication,
    JobApplicationStage,
    JobApplicationStatusChoice,
    JobChanceOption,
    JobHunt,
    JobHuntOutcomeOption,
    JobRelocationOption,
    WorkModeOption,
} from '@/types/jobHunt'
import { Form, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Ellipsis, Plus } from '@lucide/vue'
import { useStorage } from '@vueuse/core'
import { computed, onMounted, ref } from 'vue'

type View = JobApplicationStage | 'all'
type Sort = 'chance' | 'next' | 'recent'

const props = defineProps<{
    hunt: JobHunt & { is_active: boolean }
    applications: JobApplication[]
    statuses: JobApplicationStatusChoice[]
    chances: JobChanceOption[]
    workModes: WorkModeOption[]
    relocations: JobRelocationOption[]
    outcomes: JobHuntOutcomeOption[]
    followUpAfterDays: number
    today: string
}>()

const views: { key: View; label: string }[] = [
    { key: 'in_progress', label: 'In Progress' },
    { key: 'to_apply', label: 'To Apply' },
    { key: 'closed', label: 'Closed' },
    { key: 'all', label: 'All' },
]

const ALL_COUNTRIES = 'all'
const view = useStorage<View>('job-hunt.view', 'in_progress', undefined, {
    initOnMounted: true,
})
const sort = useStorage<Sort>('job-hunt.sort', 'chance', undefined, {
    initOnMounted: true,
})
const country = ref(ALL_COUNTRIES)
const search = ref('')

const selectedId = ref<number | null>(null)
const isSheetOpen = ref(false)
const isEditingHunt = ref(false)
const isEndingHunt = ref(false)
const isDeletingHunt = ref(false)
const isDeleting = ref(false)

const selectedApplication = computed(() => props.applications.find(({ id }) => id === selectedId.value) ?? null)
const stageOf = (status: string): JobApplicationStage | undefined => props.statuses.find(({ value }) => value === status)?.stage
const statusLabel = (status: string): string => props.statuses.find(({ value }) => value === status)?.label ?? status
const chanceLabel = (chance: string): string => props.chances.find(({ value }) => value === chance)?.label ?? chance
const workModeLabel = (workMode: string | null): string | null =>
    workMode ? (props.workModes.find(({ value }) => value === workMode)?.label ?? workMode) : null
const isOutsidePreferredWorkModes = (application: JobApplication): boolean =>
    application.work_mode !== null && (props.hunt.work_modes?.length ?? 0) > 0 && !props.hunt.work_modes?.includes(application.work_mode)

const countries = computed(() => {
    const counts = new Map<string, number>()

    for (const application of props.applications) {
        if (application.country) {
            counts.set(application.country, (counts.get(application.country) ?? 0) + 1)
        }
    }

    return [...counts.entries()].sort(([a, aCount], [b, bCount]) => bCount - aCount || a.localeCompare(b))
})

const inCountry = computed(() => props.applications.filter((application) => country.value === ALL_COUNTRIES || application.country === country.value))

const countFor = (key: View): number =>
    key === 'all' ? inCountry.value.length : inCountry.value.filter(({ status }) => stageOf(status) === key).length

const counts = computed(() => {
    const withStatus = (...statuses: string[]) => inCountry.value.filter(({ status }) => statuses.includes(status)).length

    return [
        {
            label: 'Applied',
            value: withStatus('applied', 'interviewing', 'offer', 'accepted', 'declined', 'rejected', 'no_response', 'withdrawn'),
        },
        { label: 'Interviewing', value: withStatus('interviewing') },
        { label: 'Offers', value: withStatus('offer', 'accepted', 'declined') },
        { label: 'Rejected', value: withStatus('rejected') },
        {
            label: 'To Follow Up',
            value: inCountry.value.filter(({ needs_follow_up }) => needs_follow_up).length,
        },
        { label: 'To Apply', value: withStatus('wishlist') },
    ]
})

const statusRank = (status: string): number => {
    const index = ['offer', 'interviewing', 'applied', 'wishlist'].indexOf(status)

    return index === -1 ? 9 : index
}

const chanceRank = (chance: string | null): number => {
    const index = ['high', 'medium', 'low'].indexOf(chance ?? '')

    return index === -1 ? 3 : index
}

const lastActivity = (application: JobApplication): string => application.events[0]?.occurred_on ?? application.applied_on ?? application.created_at

const visibleApplications = computed(() => {
    const query = search.value.trim().toLowerCase()

    return inCountry.value
        .filter(({ status }) => view.value === 'all' || stageOf(status) === view.value)
        .filter(
            (application) =>
                !query ||
                [
                    application.company,
                    application.role,
                    application.location,
                    application.country,
                    application.source,
                    application.notes,
                    application.next_step,
                ]
                    .join(' ')
                    .toLowerCase()
                    .includes(query),
        )
        .sort((a, b) => {
            if (sort.value === 'recent') {
                return lastActivity(b).localeCompare(lastActivity(a))
            }

            if (sort.value === 'chance' && chanceRank(a.chance) !== chanceRank(b.chance)) {
                return chanceRank(a.chance) - chanceRank(b.chance)
            }

            return (
                statusRank(a.status) - statusRank(b.status) ||
                (a.next_step_on ?? '9999').localeCompare(b.next_step_on ?? '9999') ||
                lastActivity(b).localeCompare(lastActivity(a))
            )
        })
})

const emptyMessage = computed((): [string, string] => {
    if (search.value.trim()) return ['Nothing matches that search.', 'Try a company name or a word from your notes.']
    if (props.applications.length === 0) return ['No jobs yet.', 'Add a job you applied for, or one you want to apply for.']
    if (view.value === 'to_apply') return ['Your to-apply list is empty.', 'Add a job with the Wishlist status to plan your next applications.']

    return ['Nothing here right now.', 'Jobs show up in this tab when their status matches.']
})

const waitingCount = computed(() => props.applications.filter(({ status }) => status === 'applied').length)

const huntPeriod = computed(() => {
    const end = props.hunt.ended_on ?? props.today
    const days = daysBetween(props.hunt.started_on, end) + 1
    const span = props.hunt.ended_on
        ? `${formatDay(props.hunt.started_on)} – ${formatDay(props.hunt.ended_on)}`
        : `Started ${formatDay(props.hunt.started_on)}`

    return `${span} · ${days === 1 ? '1 day' : `${days} days`}`
})

const outcomeLabel = computed(() => props.outcomes.find(({ value }) => value === props.hunt.outcome)?.label ?? 'Ended')

const appliedSummary = (application: JobApplication): string => {
    if (application.status === 'wishlist') {
        return application.deadline ? `Apply by ${formatDay(application.deadline, { day: 'numeric', month: 'short' })}` : 'Not applied yet'
    }

    if (!application.applied_on) {
        return ''
    }

    return `Applied ${formatDay(application.applied_on, { day: 'numeric', month: 'short' })} · ${daysBetween(application.applied_on, props.today)}d ago`
}

const openApplication = (application: JobApplication | null) => {
    selectedId.value = application?.id ?? null
    isSheetOpen.value = true
}

onMounted(() => {
    const jobId = Number(new URLSearchParams(window.location.search).get('job'))
    const application = props.applications.find(({ id }) => id === jobId)

    if (application) {
        openApplication(application)
    }
})

const deleteHunt = () => {
    router.delete(jobHuntRoutes.destroy.url(props.hunt.id), {
        onStart: () => (isDeleting.value = true),
        onFinish: () => (isDeleting.value = false),
    })
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4">
            <Link :href="jobHuntRoutes.index.url()" class="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" /> All Hunts
            </Link>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-display text-xl font-bold tracking-tight">
                            {{ hunt.title }}
                        </h1>
                        <Badge v-if="hunt.is_active">Running</Badge>
                        <Badge v-else variant="outline">{{ outcomeLabel }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ huntPeriod }}
                    </p>
                    <p v-if="hunt.goal" class="max-w-prose text-sm whitespace-pre-line">
                        {{ hunt.goal }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button type="button" size="sm" @click="openApplication(null)"><Plus /> Add a Job</Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" size="icon" class="size-8" aria-label="Hunt options"><Ellipsis /></Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @select="isEditingHunt = true">Edit Hunt</DropdownMenuItem>
                            <DropdownMenuItem v-if="hunt.is_active" @select="isEndingHunt = true">End Hunt</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem variant="destructive" @select="isDeletingHunt = true">Delete Hunt</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <dl class="flex flex-wrap gap-2">
            <div v-for="count in counts" :key="count.label" class="flex items-baseline gap-2 rounded-lg border bg-card px-3 py-2">
                <dd class="font-mono text-lg tabular-nums">
                    {{ count.value }}
                </dd>
                <dt class="text-xs tracking-wide text-muted-foreground uppercase">
                    {{ count.label }}
                </dt>
            </div>
        </dl>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1 rounded-lg bg-muted p-1" role="tablist" aria-label="Jobs by stage">
                <button
                    v-for="item in views"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="view === item.key"
                    :class="
                        cn(
                            'min-h-8 cursor-pointer rounded-md border px-3 text-sm font-medium transition-colors',
                            view === item.key
                                ? 'border-border bg-background text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="view = item.key"
                >
                    {{ item.label }}<span class="ml-1.5 font-mono text-xs opacity-70">{{ countFor(item.key) }}</span>
                </button>
            </div>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <Select v-model="country">
                    <SelectTrigger size="sm" aria-label="Filter by country" class="min-w-36">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_COUNTRIES">All Countries</SelectItem>
                        <SelectItem v-for="[name, total] in countries" :key="name" :value="name">{{ name }} ({{ total }})</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="sort">
                    <SelectTrigger size="sm" aria-label="Sort" class="min-w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="chance">Best Chance First</SelectItem>
                        <SelectItem value="next">Next Step First</SelectItem>
                        <SelectItem value="recent">Most Recent First</SelectItem>
                    </SelectContent>
                </Select>
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search company, role, notes"
                    aria-label="Search jobs"
                    class="h-8 min-w-0 flex-1 sm:w-56 sm:flex-none"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div v-if="visibleApplications.length === 0" class="px-6 py-12 text-center">
                <p class="text-sm font-medium">{{ emptyMessage[0] }}</p>
                <p class="text-sm text-muted-foreground">
                    {{ emptyMessage[1] }}
                </p>
            </div>
            <ul v-else class="divide-y divide-border">
                <li v-for="application in visibleApplications" :key="application.id">
                    <button
                        type="button"
                        class="grid w-full cursor-pointer grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-4 py-3 text-left transition-colors hover:bg-muted/50 md:grid-cols-[minmax(0,2fr)_8rem_minmax(0,1.5fr)]"
                        @click="openApplication(application)"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="truncate text-sm font-semibold">{{ application.company }}</span>
                                <span
                                    v-if="application.chance"
                                    :class="cn('rounded px-1.5 text-[11px] font-semibold tracking-wide uppercase', chanceClass(application.chance))"
                                    :title="application.chance_note ?? 'Chance of getting it'"
                                >
                                    {{ chanceLabel(application.chance) }}
                                </span>
                                <span
                                    v-if="isOutsidePreferredWorkModes(application)"
                                    class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    title="Not a work mode this hunt is looking for"
                                >
                                    Work Mode Mismatch
                                </span>
                                <span
                                    v-if="application.needs_follow_up"
                                    class="text-[11px] font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-400"
                                    :title="`Applied ${followUpAfterDays}+ days ago with nothing logged since`"
                                >
                                    Follow Up
                                </span>
                            </div>
                            <p class="truncate text-sm text-muted-foreground">
                                {{
                                    [application.role, application.location, application.country, workModeLabel(application.work_mode)]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </p>
                        </div>
                        <div>
                            <StatusBadge :status="application.status" :label="statusLabel(application.status)" />
                        </div>
                        <div class="col-span-2 min-w-0 text-sm md:col-span-1">
                            <template v-if="application.next_step">
                                <p class="truncate">
                                    {{ application.next_step }}
                                    <span v-if="application.next_step_on" class="font-mono text-xs text-muted-foreground">
                                        ·
                                        {{
                                            formatDay(application.next_step_on, {
                                                day: 'numeric',
                                                month: 'short',
                                            })
                                        }}
                                    </span>
                                </p>
                                <p class="font-mono text-xs text-muted-foreground">
                                    {{ appliedSummary(application) }}
                                </p>
                            </template>
                            <p v-else class="font-mono text-xs text-muted-foreground">
                                {{ appliedSummary(application) }}
                            </p>
                        </div>
                    </button>
                </li>
            </ul>
        </div>
        <p class="text-xs text-muted-foreground">
            Jobs marked
            <span class="font-semibold text-amber-700 uppercase dark:text-amber-400">Follow Up</span>
            were applied to {{ followUpAfterDays }} or more days ago with nothing logged since.
        </p>

        <ApplicationSheet
            v-model:open="isSheetOpen"
            :hunt-id="hunt.id"
            :application="selectedApplication"
            :statuses="statuses"
            :chances="chances"
            :work-modes="workModes"
            :relocations="relocations"
            :preferred-work-modes="hunt.work_modes ?? []"
            :new-status="view === 'to_apply' ? 'wishlist' : 'applied'"
            :today="today"
        />

        <EndHuntDialog
            v-if="hunt.is_active"
            v-model:open="isEndingHunt"
            :hunt="hunt"
            :outcomes="outcomes"
            :waiting-count="waitingCount"
            :today="today"
        />

        <Sheet v-model:open="isEditingHunt">
            <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
                <Form
                    method="put"
                    :action="jobHuntRoutes.update.url(hunt.id)"
                    :options="{ preserveScroll: true }"
                    class="flex min-h-full flex-col"
                    v-slot="{ errors, processing }"
                    @success="isEditingHunt = false"
                >
                    <SheetHeader>
                        <SheetTitle>Edit Hunt</SheetTitle>
                        <SheetDescription>Change the name, start date, what you're looking for and notes.</SheetDescription>
                    </SheetHeader>
                    <div class="flex-1 px-4 py-2">
                        <HuntFields :hunt="hunt" :work-modes="workModes" :today="today" :errors="errors" />
                    </div>
                    <SheetFooter>
                        <Button type="submit" :disabled="processing">Save</Button>
                    </SheetFooter>
                </Form>
            </SheetContent>
        </Sheet>

        <ConfirmDialog
            v-model:open="isDeletingHunt"
            :title="`Delete ${hunt.title}?`"
            :description="`This deletes the hunt and all ${applications.length} jobs in it, with their timelines. It can't be undone.`"
            confirm-label="Delete Hunt"
            :processing="isDeleting"
            @confirm="deleteHunt"
        />
    </div>
</template>
