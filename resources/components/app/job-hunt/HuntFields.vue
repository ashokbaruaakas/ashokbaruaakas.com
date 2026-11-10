<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import type { JobHunt, WorkModeOption } from '@/types/jobHunt'

defineProps<{
    hunt?: JobHunt
    workModes: WorkModeOption[]
    today: string
    errors: Record<string, string | undefined>
}>()
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="hunt-title">Name<span aria-hidden="true"> *</span></Label>
            <Input
                id="hunt-title"
                name="title"
                :default-value="hunt?.title ?? ''"
                placeholder="Senior Laravel roles, late 2026"
                maxlength="120"
                required
            />
            <InputError :message="errors.title" />
        </div>
        <div class="grid gap-2">
            <Label for="hunt-started-on">Started On<span aria-hidden="true"> *</span></Label>
            <Input
                id="hunt-started-on"
                name="started_on"
                type="date"
                :default-value="hunt?.started_on ?? today"
                :max="today"
                required
                class="w-fit"
            />
            <InputError :message="errors.started_on" />
        </div>
        <fieldset class="grid gap-3">
            <legend class="mb-2 text-sm font-medium">Work Modes You'd Take</legend>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                <div v-for="mode in workModes" :key="mode.value" class="flex items-center gap-2">
                    <Checkbox
                        :id="`hunt-work-mode-${mode.value}`"
                        name="work_modes[]"
                        :value="String(mode.value)"
                        :default-value="hunt?.work_modes?.includes(String(mode.value)) ?? false"
                    />
                    <Label :for="`hunt-work-mode-${mode.value}`" class="font-normal">{{ mode.label }}</Label>
                </div>
            </div>
            <InputError :message="errors.work_modes ?? errors['work_modes.0']" />
        </fieldset>
        <div class="grid gap-2">
            <Label for="hunt-countries">Countries</Label>
            <Input
                id="hunt-countries"
                name="countries"
                :default-value="hunt?.countries ?? ''"
                placeholder="Germany, Netherlands, anywhere in the EU"
                maxlength="255"
            />
            <InputError :message="errors.countries" />
        </div>
        <div class="flex items-start gap-3">
            <Checkbox id="hunt-open-to-relocation" name="open_to_relocation" value="1" :default-value="hunt?.open_to_relocation ?? false" />
            <div class="grid gap-1">
                <Label for="hunt-open-to-relocation">Open to Relocating</Label>
                <p class="text-sm text-muted-foreground">You'd move to another city or country for the right job.</p>
            </div>
        </div>
        <div class="grid gap-2">
            <Label for="hunt-goal">What Else You're Looking For</Label>
            <Textarea
                id="hunt-goal"
                name="goal"
                :default-value="hunt?.goal ?? ''"
                placeholder="Role, seniority, salary floor, anything else that rules a job in or out"
                rows="3"
            />
            <InputError :message="errors.goal" />
        </div>
        <div class="grid gap-2">
            <Label for="hunt-notes">Notes</Label>
            <Textarea id="hunt-notes" name="notes" :default-value="hunt?.notes ?? ''" rows="3" />
            <InputError :message="errors.notes" />
        </div>
    </div>
</template>
