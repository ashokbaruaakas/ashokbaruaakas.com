<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import type { JobHunt } from '@/types/jobHunt'

defineProps<{
    hunt?: JobHunt
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
        <div class="grid gap-2">
            <Label for="hunt-goal">What You're Looking For</Label>
            <Textarea
                id="hunt-goal"
                name="goal"
                :default-value="hunt?.goal ?? ''"
                placeholder="Role, countries or remote, salary floor, anything that rules a job in or out"
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
