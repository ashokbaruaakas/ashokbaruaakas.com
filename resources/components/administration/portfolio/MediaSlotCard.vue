<script setup lang="ts">
import ConfirmDialog from '@/components/administration/portfolio/ConfirmDialog.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import administrationPortfolio from '@/routes/administration/portfolio'
import type { PortfolioMediaSlot } from '@/types/portfolio'
import { router, useForm } from '@inertiajs/vue3'
import { ImageOff, Upload } from '@lucide/vue'
import { ref, useTemplateRef } from 'vue'

const props = defineProps<{ slot: PortfolioMediaSlot }>()

const input = useTemplateRef<HTMLInputElement>('input')
const isConfirmingRemoval = ref(false)
const isRemoving = ref(false)

const form = useForm<{ file: File | null }>({ file: null })

const upload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0]

    if (!file) {
        return
    }

    form.file = file
    form.post(administrationPortfolio.media.update.url(props.slot.key), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.reset()

            if (input.value) {
                input.value.value = ''
            }
        },
    })
}

const remove = () => {
    router.delete(administrationPortfolio.media.destroy.url(props.slot.key), {
        preserveScroll: true,
        onStart: () => (isRemoving.value = true),
        onFinish: () => {
            isRemoving.value = false
            isConfirmingRemoval.value = false
        },
    })
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ slot.title }}</CardTitle>
            <CardDescription>{{ slot.description }}</CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-md border border-border bg-muted">
                <img v-if="slot.url" :src="slot.url" :alt="`Current ${slot.title}`" class="size-full object-contain" />
                <ImageOff v-else class="size-5 text-muted-foreground" aria-hidden="true" />
            </div>
            <div class="grid gap-2">
                <input ref="input" type="file" class="sr-only" :accept="slot.accept" :aria-label="`Upload ${slot.title}`" @change="upload" />
                <div class="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" :disabled="form.processing" @click="input?.click()">
                        <Upload /> {{ form.processing ? 'Uploading…' : slot.url ? 'Replace' : 'Upload' }}
                    </Button>
                    <Button v-if="slot.url" type="button" variant="ghost" size="sm" @click="isConfirmingRemoval = true">Remove</Button>
                </div>
                <InputError :message="form.errors.file" />
            </div>
        </CardContent>
    </Card>

    <ConfirmDialog
        v-model:open="isConfirmingRemoval"
        :title="`Remove ${slot.title}?`"
        description="It will disappear from your public pages."
        confirm-label="Remove"
        :processing="isRemoving"
        @confirm="remove"
    />
</template>
