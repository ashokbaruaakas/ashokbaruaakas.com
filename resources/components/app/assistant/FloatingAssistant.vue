<script setup lang="ts">
import AssistantPanel from '@/components/app/assistant/AssistantPanel.vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import type { AssistantConversation, AssistantConversationSummary, AssistantProposal } from '@/types/assistant'
import { Sparkles } from '@lucide/vue'
import { nextTick, ref, watch, type HTMLAttributes } from 'vue'

const props = withDefaults(
    defineProps<{
        assistant: { isReady: boolean; provider: string; model: string; conversations: Omit<AssistantConversationSummary, 'job'>[] }
        conversation: AssistantConversation | null | undefined
        subject: string
        intro: string
        context: () => Record<string, unknown>
        loadsIntoEditor?: (proposal: AssistantProposal) => boolean
        loadedProposalIds?: number[]
        reloads?: string[]
        class?: HTMLAttributes['class']
    }>(),
    { loadsIntoEditor: () => false, loadedProposalIds: () => [], reloads: () => [] },
)

const emit = defineEmits<{
    load: [proposal: AssistantProposal]
    unload: [proposal: AssistantProposal]
}>()

const storageKey = 'assistant-window-open'

const readIsOpen = (): boolean => {
    try {
        return window.localStorage.getItem(storageKey) === '1'
    } catch {
        return false
    }
}

const isOpen = ref(readIsOpen())
const hasOpened = ref(isOpen.value)
const panel = ref<InstanceType<typeof AssistantPanel> | null>(null)

watch(isOpen, (open) => {
    try {
        window.localStorage.setItem(storageKey, open ? '1' : '0')
    } catch {}
})

const open = () => {
    isOpen.value = true
    hasOpened.value = true
}

const ask = async (text: string) => {
    open()
    await nextTick()
    panel.value?.ask(text)
}

defineExpose({ ask })
</script>

<template>
    <div :class="cn('pointer-events-none fixed right-4 bottom-4 z-40 flex flex-col items-end', props.class)" data-assistant-window>
        <AssistantPanel
            v-if="hasOpened"
            v-show="isOpen"
            ref="panel"
            class="pointer-events-auto h-[min(40rem,calc(100dvh-2rem))] w-[min(26rem,calc(100vw-2rem))] shadow-2xl"
            :assistant="assistant"
            :conversation="conversation"
            :subject="subject"
            :intro="intro"
            :context="context"
            :loads-into-editor="loadsIntoEditor"
            :loaded-proposal-ids="loadedProposalIds"
            :reloads="reloads"
            close-label="Minimize"
            minimizes
            @close="isOpen = false"
            @load="emit('load', $event)"
            @unload="emit('unload', $event)"
        />
        <Button
            v-show="!isOpen"
            type="button"
            class="pointer-events-auto size-12 rounded-full shadow-lg"
            size="icon"
            title="Ask the Assistant"
            aria-label="Ask the Assistant"
            @click="open"
        >
            <Sparkles class="size-5" />
        </Button>
    </div>
</template>
