<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { PortfolioLinkValue } from '@/types/portfolio'
import { Trash2 } from '@lucide/vue'
import { useId } from 'vue'

const props = defineProps<{
    link: PortfolioLinkValue
    index: number
}>()

defineEmits<{
    update: [changes: Partial<PortfolioLinkValue>]
    remove: []
}>()

const primaryId = useId()
const position = props.index + 1
</script>

<template>
    <div class="grid gap-2 rounded-md border border-border p-3">
        <Input
            :model-value="link.label"
            placeholder="Label"
            :aria-label="`Link ${position} label`"
            @update:model-value="$emit('update', { label: String($event) })"
        />
        <Input
            :model-value="link.url"
            type="url"
            placeholder="https://"
            :aria-label="`Link ${position} URL`"
            @update:model-value="$emit('update', { url: String($event) })"
        />
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Checkbox :id="primaryId" :model-value="link.is_primary" @update:model-value="$emit('update', { is_primary: Boolean($event) })" />
                <Label :for="primaryId">Primary</Label>
            </div>
            <Button type="button" variant="ghost" size="sm" @click="$emit('remove')"><Trash2 /> Remove</Button>
        </div>
    </div>
</template>
