<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { LoaderCircle, Sparkles, TriangleAlert, Undo2 } from '@lucide/vue'
import { ref } from 'vue'

const props = defineProps<{
    id: string
    label: string
    base: string | string[]
    showsStaleWarning: boolean
    multiline?: boolean
    placeholder?: string
    error?: string
    reword?: () => Promise<string>
    rewordDisabled?: boolean
}>()

const emit = defineEmits<{
    keepMine: []
}>()

const text = defineModel<string>({ required: true })

const isRewording = ref(false)
const rewordError = ref<string | null>(null)
const previousText = ref<string | null>(null)

const rewordWithAssistant = async () => {
    if (!props.reword || isRewording.value) {
        return
    }

    isRewording.value = true
    rewordError.value = null

    try {
        const reworded = await props.reword()

        previousText.value = text.value
        text.value = reworded
    } catch (error) {
        rewordError.value = error instanceof Error ? error.message : 'The assistant could not reword it. Please try again.'
    } finally {
        isRewording.value = false
    }
}

const restorePreviousText = () => {
    text.value = previousText.value ?? ''
    previousText.value = null
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <Label :for="id">{{ label }}</Label>
            <div v-if="reword" class="flex items-center gap-1">
                <Button v-if="previousText !== null && !isRewording" type="button" variant="ghost" size="sm" class="h-7" @click="restorePreviousText">
                    <Undo2 />
                    Restore Your Text
                </Button>
                <Button type="button" variant="outline" size="sm" class="h-7" :disabled="isRewording || rewordDisabled" @click="rewordWithAssistant">
                    <LoaderCircle v-if="isRewording" class="animate-spin" />
                    <Sparkles v-else />
                    {{ isRewording ? 'Rewording…' : 'Reword With the Assistant' }}
                </Button>
            </div>
        </div>
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
        <Textarea v-if="multiline" :id="id" v-model="text" rows="4" :placeholder="placeholder" :disabled="isRewording" />
        <Input v-else :id="id" v-model="text" :placeholder="placeholder" :disabled="isRewording" />
        <p class="text-xs text-muted-foreground">
            {{
                previousText !== null
                    ? 'The assistant wrote this. Edit it as you like, and save to keep it.'
                    : 'Leave it empty to use the portfolio text.'
            }}
        </p>
        <InputError :message="rewordError ?? error" />
    </div>
</template>
