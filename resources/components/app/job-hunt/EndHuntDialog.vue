<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'
import jobHuntRoutes from '@/routes/job-hunt'
import type { JobHunt, JobHuntOutcomeOption } from '@/types/jobHunt'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
    hunt: JobHunt
    outcomes: JobHuntOutcomeOption[]
    waitingCount: number
    today: string
}>()

const open = defineModel<boolean>('open', { required: true })

const form = useForm({
    outcome: 'hired',
    ended_on: props.today,
    close_waiting_applications: false,
})

const end = () => {
    form.post(jobHuntRoutes.end.url(props.hunt.id), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    })
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form class="grid gap-6" @submit.prevent="end">
                <DialogHeader>
                    <DialogTitle>End This Hunt?</DialogTitle>
                    <DialogDescription>You can still update its jobs afterwards, for example when a late reply comes in.</DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label>How Did It End?</Label>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="How Did It End?">
                        <button
                            v-for="outcome in outcomes"
                            :key="outcome.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.outcome === outcome.value"
                            :class="
                                cn(
                                    'cursor-pointer rounded-full border px-3 py-1 text-sm font-medium transition-colors',
                                    form.outcome === outcome.value
                                        ? 'border-transparent bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                            @click="form.outcome = String(outcome.value)"
                        >
                            {{ outcome.label }}
                        </button>
                    </div>
                    <InputError :message="form.errors.outcome" />
                </div>

                <div class="grid gap-2">
                    <Label for="hunt-ended-on">Ended On</Label>
                    <Input id="hunt-ended-on" v-model="form.ended_on" type="date" :min="hunt.started_on" :max="today" class="w-fit" />
                    <InputError :message="form.errors.ended_on" />
                </div>

                <div v-if="waitingCount > 0" class="flex items-start gap-3">
                    <Checkbox id="hunt-close-waiting" v-model="form.close_waiting_applications" />
                    <div class="grid gap-1">
                        <Label for="hunt-close-waiting">Mark the {{ waitingCount }} jobs still waiting on a reply as “No Response”</Label>
                        <p class="text-sm text-muted-foreground">Jobs in “Applied” status. Interviews and offers are left as they are.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">End Hunt</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
