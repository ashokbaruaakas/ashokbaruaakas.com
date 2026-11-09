<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { formatRelativeDate } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import type { AtsCheck } from '@/types/ats'
import { CircleCheck, CircleAlert, ScanSearch } from '@lucide/vue'
import { computed, ref } from 'vue'
import AtsCheckDialog from './AtsCheckDialog.vue'

const props = defineProps<{
    check?: AtsCheck | null
    canMatchJob: boolean
}>()

const isDialogOpen = ref(false)

const radius = 52
const circumference = 2 * Math.PI * radius

const tone = (score: number) => (score >= 80 ? 'good' : score >= 60 ? 'fair' : 'poor')

const toneClasses = {
    good: { stroke: 'stroke-emerald-500', text: 'text-emerald-700 dark:text-emerald-400', label: 'Strong' },
    fair: { stroke: 'stroke-amber-500', text: 'text-amber-700 dark:text-amber-400', label: 'Needs Work' },
    poor: { stroke: 'stroke-red-500', text: 'text-red-700 dark:text-red-400', label: 'Weak' },
}

const scoreTone = computed(() => (props.check ? toneClasses[tone(props.check.score)] : toneClasses.poor))

const sortedChecks = computed(() => [...(props.check?.checks ?? [])].sort((first, second) => first.points / first.max - second.points / second.max))

const passedCount = computed(() => sortedChecks.value.filter(({ points, max }) => points === max).length)
</script>

<template>
    <section aria-labelledby="ats-card-title" class="flex flex-col gap-4 rounded-lg border p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <ScanSearch class="size-4 text-muted-foreground" aria-hidden="true" />
                <h2 id="ats-card-title" class="text-sm font-semibold">ATS Score</h2>
                <span v-if="check?.checked_at" class="text-xs text-muted-foreground">· Checked {{ formatRelativeDate(check.checked_at) }}</span>
            </div>
            <Button size="sm" variant="outline" @click="isDialogOpen = true">{{ check ? 'Run Again' : 'Run Check' }}</Button>
        </div>

        <div v-if="check === undefined" class="flex items-center gap-6">
            <Skeleton class="size-32 rounded-full" />
            <div class="flex flex-1 flex-col gap-2">
                <Skeleton v-for="index in 4" :key="index" class="h-4 w-full" />
            </div>
        </div>

        <p v-else-if="check === null" class="text-sm text-muted-foreground">
            No check yet. Run one to see how applicant tracking systems read your resume, and what to fix first.
        </p>

        <div v-else class="grid gap-6 md:grid-cols-[auto_minmax(0,1fr)]">
            <div class="flex flex-col items-center gap-3">
                <div class="relative size-32">
                    <svg viewBox="0 0 120 120" class="size-full -rotate-90" aria-hidden="true">
                        <circle cx="60" cy="60" :r="radius" class="fill-none stroke-muted" stroke-width="10" />
                        <circle
                            cx="60"
                            cy="60"
                            :r="radius"
                            :class="cn('fill-none transition-all duration-700', scoreTone.stroke)"
                            stroke-width="10"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="circumference * (1 - check.score / 100)"
                        />
                    </svg>
                    <div
                        role="meter"
                        :aria-valuenow="check.score"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="ATS score"
                        class="absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="font-display text-3xl font-bold tabular-nums">{{ check.score }}</span>
                        <span :class="cn('text-xs font-medium', scoreTone.text)">{{ scoreTone.label }}</span>
                    </div>
                </div>

                <dl class="grid w-full grid-cols-2 gap-2 text-center">
                    <div class="rounded-md bg-muted/60 px-2.5 py-2">
                        <dt class="text-xs text-muted-foreground">Resume</dt>
                        <dd class="font-display text-lg font-bold tabular-nums">{{ check.resume_score }}</dd>
                    </div>
                    <div class="rounded-md bg-muted/60 px-2.5 py-2">
                        <dt class="text-xs text-muted-foreground">Job Match</dt>
                        <dd class="font-display text-lg font-bold tabular-nums">{{ check.job_match_score ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <div v-if="check.job_title" class="flex flex-col gap-2">
                    <h3 class="text-xs font-medium text-muted-foreground">Keywords from {{ check.job_title }}</h3>
                    <ul class="flex flex-wrap gap-1.5" aria-label="Job keywords">
                        <li
                            v-for="keyword in check.matched_keywords ?? []"
                            :key="`matched-${keyword}`"
                            class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300"
                        >
                            {{ keyword }}
                        </li>
                        <li
                            v-for="keyword in check.missing_keywords ?? []"
                            :key="`missing-${keyword}`"
                            class="rounded-full border border-dashed px-2 py-0.5 text-xs font-medium text-muted-foreground line-through decoration-red-500/60"
                        >
                            <span class="sr-only">Missing: </span>{{ keyword }}
                        </li>
                    </ul>
                    <p v-if="(check.missing_keywords ?? []).length > 0" class="text-xs text-muted-foreground">
                        Add the missing keywords you really have to your skills and highlights, in the post's exact wording.
                    </p>
                </div>

                <div class="flex flex-col gap-1">
                    <h3 class="text-xs font-medium text-muted-foreground">{{ passedCount }} of {{ sortedChecks.length }} Checks Passed</h3>
                    <ul class="flex flex-col divide-y">
                        <li v-for="item in sortedChecks" :key="item.key" class="flex items-start gap-2.5 py-2 text-sm">
                            <CircleCheck v-if="item.points === item.max" class="mt-0.5 size-4 shrink-0 text-emerald-600" aria-hidden="true" />
                            <CircleAlert
                                v-else
                                :class="cn('mt-0.5 size-4 shrink-0', item.points === 0 ? 'text-red-600' : 'text-amber-600')"
                                aria-hidden="true"
                            />
                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="font-medium">{{ item.label }}</span>
                                    <span class="text-xs text-muted-foreground tabular-nums">{{ item.points }}/{{ item.max }}</span>
                                </div>
                                <p v-if="item.tip" class="text-muted-foreground">{{ item.tip }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <AtsCheckDialog v-model:open="isDialogOpen" :can-match-job="canMatchJob" />
    </section>
</template>
