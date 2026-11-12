<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { TriangleAlert } from '@lucide/vue'

defineProps<{
    id: string
    label: string
    base: string | string[]
    showsStaleWarning: boolean
    multiline?: boolean
    placeholder?: string
    error?: string
}>()

const emit = defineEmits<{
    keepMine: []
}>()

const text = defineModel<string>({ required: true })
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">{{ label }}</Label>
        <div class="rounded-md bg-muted/50 px-2.5 py-1.5 text-sm text-muted-foreground">
            <span class="font-medium">Portfolio: </span>
            <ul v-if="Array.isArray(base)" class="mt-1 list-disc pl-5">
                <li v-for="line in base" :key="line">{{ line }}</li>
            </ul>
            <template v-else>{{ base || 'empty' }}</template>
        </div>
        <p
            v-if="showsStaleWarning"
            class="flex flex-wrap items-center gap-2 rounded-md bg-amber-500/10 px-2.5 py-1.5 text-sm text-amber-700 dark:text-amber-400"
        >
            <TriangleAlert class="size-4" aria-hidden="true" />
            The portfolio text changed since you wrote this.
            <Button type="button" variant="link" size="sm" class="h-auto p-0" @click="emit('keepMine')">Keep Mine</Button>
            <Button type="button" variant="link" size="sm" class="h-auto p-0" @click="text = ''">Use the Portfolio Text</Button>
        </p>
        <Textarea v-if="multiline" :id="id" v-model="text" rows="4" :placeholder="placeholder" />
        <Input v-else :id="id" v-model="text" :placeholder="placeholder" />
        <p class="text-xs text-muted-foreground">Leave it empty to use the portfolio text.</p>
        <InputError :message="error" />
    </div>
</template>
