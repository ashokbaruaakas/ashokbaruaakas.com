<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

const { confirmVariant = 'destructive' } = defineProps<{
    title: string
    description: string
    confirmLabel: string
    processing?: boolean
    confirmVariant?: 'destructive' | 'default'
}>()

const open = defineModel<boolean>('open', { required: true })

const emit = defineEmits<{
    confirm: []
}>()
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                <Button type="button" :variant="confirmVariant" :disabled="processing" @click="emit('confirm')">{{ confirmLabel }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
