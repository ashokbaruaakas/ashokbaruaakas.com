<script setup lang="ts">
import CoverLetterCard from '@/components/app/assistant/CoverLetterCard.vue'
import MarkdownBlocks from '@/components/app/assistant/MarkdownBlocks.vue'
import ProposalCard from '@/components/app/assistant/ProposalCard.vue'
import { parseMarkdown } from '@/components/app/assistant/markdown'
import { streamAssistantReply, toolActivity, visibleText } from '@/components/app/assistant/streamReply'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { formatRelativeDate } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import assistantRoutes from '@/routes/assistant'
import settingsRoutes from '@/routes/settings'
import type { AssistantConversation, AssistantConversationSummary, AssistantMessage, AssistantProposal } from '@/types/assistant'
import { Link, router } from '@inertiajs/vue3'
import { ArrowUp, ExternalLink, LoaderCircle, Minus, Plus, X } from '@lucide/vue'
import { computed, nextTick, onMounted, ref, watch } from 'vue'

const props = withDefaults(
    defineProps<{
        assistant: { isReady: boolean; provider: string; model: string; conversations: Omit<AssistantConversationSummary, 'job'>[] }
        conversation: AssistantConversation | null | undefined
        subject: string
        intro: string
        quickRequests?: { label: string; message: string }[]
        context: () => Record<string, unknown>
        loadsIntoEditor: (proposal: AssistantProposal) => boolean
        loadedProposalIds: number[]
        reloads?: string[]
        closeLabel?: string
        minimizes?: boolean
    }>(),
    { quickRequests: () => [], reloads: () => [], closeLabel: 'Back to Editing', minimizes: false },
)

const emit = defineEmits<{
    close: []
    load: [proposal: AssistantProposal]
    unload: [proposal: AssistantProposal]
}>()

const conversationId = ref<string | null>(props.conversation?.id ?? props.assistant.conversations[0]?.id ?? null)
const messages = ref<AssistantMessage[]>([...(props.conversation?.messages ?? [])])
const draft = ref('')
const isStreaming = ref(false)
const activity = ref<string | null>(null)
const scroller = ref<HTMLElement | null>(null)
const composer = ref<HTMLElement | null>(null)

const scrollToEnd = () => nextTick(() => scroller.value?.scrollTo({ top: scroller.value.scrollHeight }))

const loadConversation = (id: string) =>
    router.reload({
        only: ['assistantConversation', 'assistant', ...props.reloads],
        data: { conversation: id },
        onSuccess: scrollToEnd,
    })

watch(
    () => props.conversation,
    (conversation) => {
        if (conversation === null && conversationId.value !== null && !isStreaming.value) {
            conversationId.value = null
        }

        if (conversation && conversation.id === conversationId.value) {
            messages.value = [...conversation.messages]
        }
    },
)

onMounted(() => {
    if (conversationId.value !== null && props.conversation?.id !== conversationId.value) {
        loadConversation(conversationId.value)
    }

    scrollToEnd()
})

const startNewConversation = () => {
    conversationId.value = null
    messages.value = []
}

const conversationTitle = computed(() =>
    conversationId.value === null
        ? 'New Conversation'
        : (props.assistant.conversations.find(({ id }) => id === conversationId.value)?.title ?? props.conversation?.title ?? 'Conversation'),
)

const openConversation = (id: unknown) => {
    if (typeof id !== 'string' || id === conversationId.value || isStreaming.value) {
        return
    }

    conversationId.value = id
    messages.value = []
    loadConversation(id)
}

const send = async (text = draft.value) => {
    const message = text.trim()

    if (!props.assistant.isReady || isStreaming.value || message === '') {
        return
    }

    const sentAt = new Date().toISOString()

    messages.value.push({ id: crypto.randomUUID(), role: 'user', content: message, failed: false, created_at: sentAt })
    messages.value.push({ id: crypto.randomUUID(), role: 'assistant', content: '', failed: false, created_at: sentAt })
    const reply = messages.value[messages.value.length - 1]!

    draft.value = ''
    isStreaming.value = true
    activity.value = 'Thinking…'
    scrollToEnd()

    try {
        const id = await streamAssistantReply(
            { message, conversation: conversationId.value, ...props.context() },
            {
                onText: (delta) => {
                    reply.content += delta
                    activity.value = null
                    scrollToEnd()
                },
                onTool: (toolName) => (activity.value = toolActivity(toolName)),
            },
        )

        if (id) {
            conversationId.value = id
        }
    } catch (error) {
        reply.failed = true
        reply.content = error instanceof Error ? error.message : String(error)
    } finally {
        isStreaming.value = false
        activity.value = null
    }

    if (!reply.failed && conversationId.value) {
        loadConversation(conversationId.value)
    }
}

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault()
        send()
    }
}

const ask = (text: string) => {
    draft.value = text

    nextTick(() => {
        const field = composer.value?.querySelector('textarea')

        field?.focus()
        field?.setSelectionRange(text.length, text.length)
    })
}

defineExpose({ ask })
</script>

<template>
    <section class="flex flex-col rounded-xl border bg-card" aria-label="Assistant">
        <header class="flex min-h-12 items-center justify-between gap-2 border-b px-4 py-2">
            <h2 v-if="!assistant.conversations.length" class="text-sm font-semibold">Assistant</h2>
            <Select v-else :model-value="conversationId ?? undefined" :disabled="isStreaming" @update:model-value="openConversation">
                <SelectTrigger size="sm" class="min-w-0 flex-1" aria-label="Conversation">
                    <SelectValue placeholder="New Conversation">{{ conversationTitle }}</SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="item in assistant.conversations" :key="item.id" :value="item.id">
                        <span class="truncate">{{ item.title }}</span>
                        <span v-if="item.updated_at" class="text-xs text-muted-foreground">{{ formatRelativeDate(item.updated_at) }}</span>
                    </SelectItem>
                </SelectContent>
            </Select>
            <div class="flex shrink-0 items-center gap-1">
                <Button type="button" v-if="conversationId" variant="ghost" size="sm" :disabled="isStreaming" @click="startNewConversation">
                    <Plus />
                    New Conversation
                </Button>
                <Button
                    v-if="conversationId"
                    as-child
                    variant="ghost"
                    size="icon-sm"
                    title="Open in the Assistant"
                    aria-label="Open in the Assistant"
                >
                    <Link :href="assistantRoutes.show.url(conversationId)"><ExternalLink /></Link>
                </Button>
                <Button type="button" variant="ghost" size="icon-sm" :title="closeLabel" :aria-label="closeLabel" @click="emit('close')">
                    <Minus v-if="minimizes" />
                    <X v-else />
                </Button>
            </div>
        </header>

        <div v-if="!assistant.isReady" class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
            <p class="text-sm text-muted-foreground">Add an API key for {{ assistant.provider }} to get the assistant's help with {{ subject }}.</p>
            <Button as-child size="sm">
                <Link :href="settingsRoutes.ai.edit.url()">Open AI Settings</Link>
            </Button>
        </div>

        <template v-else>
            <div ref="scroller" class="flex flex-1 flex-col gap-4 overflow-y-auto p-4">
                <p v-if="!messages.length" class="m-auto max-w-xs py-8 text-center text-sm text-muted-foreground">
                    {{ intro }}
                </p>

                <div
                    v-for="message in messages"
                    :key="message.id"
                    :class="cn('flex flex-col gap-1', message.role === 'user' ? 'items-end' : 'items-start')"
                >
                    <div
                        v-if="visibleText(message.content) || !(message.proposals?.length || message.coverLetters?.length)"
                        :class="
                            cn(
                                'max-w-[90%] min-w-0 rounded-xl px-3.5 py-2 text-sm leading-relaxed break-words',
                                message.role === 'user' ? 'bg-primary whitespace-pre-wrap text-primary-foreground' : 'bg-muted',
                                message.failed && 'border border-destructive/40 bg-destructive/10 text-destructive',
                            )
                        "
                    >
                        <MarkdownBlocks
                            v-if="message.role === 'assistant' && !message.failed && visibleText(message.content)"
                            :blocks="parseMarkdown(visibleText(message.content))"
                            :jobs="[]"
                        />
                        <template v-else-if="visibleText(message.content)">{{ visibleText(message.content) }}</template>
                        <span v-else class="inline-flex items-center gap-2 text-muted-foreground">
                            <LoaderCircle class="size-4 animate-spin" />
                            {{ activity ?? 'Thinking…' }}
                        </span>
                    </div>
                    <ProposalCard
                        v-for="proposal in message.proposals"
                        :key="`proposal-${proposal.id}`"
                        :proposal="proposal"
                        :reloads="['assistantConversation', ...reloads]"
                        :loads-into-editor="loadsIntoEditor(proposal)"
                        :is-loaded="loadedProposalIds.includes(proposal.id)"
                        class="max-w-full"
                        @load="emit('load', proposal)"
                        @unload="emit('unload', proposal)"
                    />
                    <CoverLetterCard v-for="letter in message.coverLetters" :key="`letter-${letter.id}`" :letter="letter" />
                </div>
            </div>

            <div v-if="quickRequests.length" class="flex flex-wrap gap-1.5 border-t px-3 pt-3">
                <Button
                    type="button"
                    v-for="request in quickRequests"
                    :key="request.label"
                    variant="outline"
                    size="sm"
                    class="h-7 rounded-full px-2.5 text-xs"
                    :disabled="isStreaming"
                    @click="send(request.message)"
                >
                    {{ request.label }}
                </Button>
            </div>

            <div ref="composer" :class="cn('flex items-end gap-2 p-3', !quickRequests.length && 'border-t')">
                <Textarea
                    v-model="draft"
                    rows="2"
                    maxlength="20000"
                    aria-label="Message"
                    :placeholder="`Ask about ${subject}…`"
                    class="max-h-40 min-h-11 resize-none"
                    @keydown="onKeydown"
                />
                <Button type="button" size="icon" :disabled="isStreaming || !draft.trim()" aria-label="Send" @click="send()">
                    <ArrowUp />
                </Button>
            </div>
            <p class="px-4 pb-3 text-xs text-muted-foreground">
                Answers come from {{ assistant.provider }} ({{ assistant.model }}) and can be wrong. Check before you save.
            </p>
        </template>
    </section>
</template>
