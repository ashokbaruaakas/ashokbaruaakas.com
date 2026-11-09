<script setup lang="ts">
import HuntFields from '@/components/app/job-hunt/HuntFields.vue'
import { daysBetween, formatDay } from '@/components/app/job-hunt/jobHunt'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import jobHuntRoutes from '@/routes/job-hunt'
import type { JobHuntSummary, WorkModeOption } from '@/types/jobHunt'
import { Form, Link } from '@inertiajs/vue3'
import { ArrowRight } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps<{
    hunts: JobHuntSummary[]
    workModes: WorkModeOption[]
    today: string
}>()

const activeHunt = computed(() => props.hunts.find((hunt) => hunt.ended_on === null) ?? null)
const pastHunts = computed(() => props.hunts.filter((hunt) => hunt.ended_on !== null))

const duration = (hunt: JobHuntSummary): string => {
    const days = daysBetween(hunt.started_on, hunt.ended_on ?? props.today) + 1

    return days === 1 ? '1 day' : `${days} days`
}

const outcomeLabel = (outcome: string | null): string => (outcome ? outcome.charAt(0).toUpperCase() + outcome.slice(1) : 'Ended')
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-xl font-bold tracking-tight">Job Hunt</h1>
            <p class="text-sm text-muted-foreground">
                Each hunt runs from the day you start looking until you stop. Track every job in it, from wishlist to offer.
            </p>
        </div>

        <Link
            v-if="activeHunt"
            :href="jobHuntRoutes.show.url(activeHunt.id)"
            class="group rounded-xl border bg-card p-6 transition-colors hover:border-foreground/30"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <Badge>Running</Badge>
                        <span class="text-sm text-muted-foreground">Started {{ formatDay(activeHunt.started_on) }} · {{ duration(activeHunt) }}</span>
                    </div>
                    <h2 class="truncate text-lg font-semibold">
                        {{ activeHunt.title }}
                    </h2>
                    <p v-if="activeHunt.goal" class="line-clamp-2 text-sm text-muted-foreground">
                        {{ activeHunt.goal }}
                    </p>
                </div>
                <ArrowRight class="size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
            </div>
            <dl class="mt-6 grid grid-cols-3 gap-4 sm:max-w-md">
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">Jobs</dt>
                    <dd class="font-mono text-xl tabular-nums">
                        {{ activeHunt.applications_count }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">Applied</dt>
                    <dd class="font-mono text-xl tabular-nums">
                        {{ activeHunt.applied_count }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">Offers</dt>
                    <dd class="font-mono text-xl tabular-nums">
                        {{ activeHunt.offers_count }}
                    </dd>
                </div>
            </dl>
        </Link>

        <Form v-else method="post" :action="jobHuntRoutes.store.url()" v-slot="{ errors, processing }">
            <Card>
                <CardHeader>
                    <CardTitle>Start a Hunt</CardTitle>
                    <CardDescription>
                        {{ pastHunts.length ? 'You have no hunt running.' : 'You have no hunts yet.' }}
                        Start one when you begin looking for a job.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <HuntFields :work-modes="workModes" :today="today" :errors="errors" />
                </CardContent>
                <CardFooter class="justify-end">
                    <Button type="submit" size="sm" :disabled="processing">Start Hunt</Button>
                </CardFooter>
            </Card>
        </Form>

        <section v-if="pastHunts.length" class="flex flex-col gap-3">
            <h2 class="text-sm font-semibold">Past Hunts</h2>
            <ol class="divide-y divide-border rounded-xl border">
                <li v-for="hunt in pastHunts" :key="hunt.id">
                    <Link
                        :href="jobHuntRoutes.show.url(hunt.id)"
                        class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 transition-colors hover:bg-muted/50"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ hunt.title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDay(hunt.started_on) }} – {{ formatDay(hunt.ended_on ?? today) }} ·
                                {{ duration(hunt) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                            <span class="tabular-nums">{{ hunt.applied_count }} applied</span>
                            <span class="tabular-nums">{{ hunt.offers_count }} {{ hunt.offers_count === 1 ? 'offer' : 'offers' }}</span>
                            <Badge variant="outline">{{ outcomeLabel(hunt.outcome) }}</Badge>
                        </div>
                    </Link>
                </li>
            </ol>
        </section>
    </div>
</template>
