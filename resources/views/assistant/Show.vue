<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { cn } from '@/lib/utils'
import assistantRoutes from '@/routes/assistant'
import settingsRoutes from '@/routes/settings'
import type { AssistantConversation, AssistantConversationSummary, AssistantMessage } from '@/types/assistant'
import { Link, router } from '@inertiajs/vue3'
import { ArrowUp, LoaderCircle, Plus, Trash2 } from '@lucide/vue'
import { computed, nextTick, ref, watch } from 'vue'

const props = defineProps<{
    conversations: AssistantConversationSummary[]
    conversation: AssistantConversation | null
    isReady: boolean
    provider: string
    model: string
}>()

const suggestions = [
    'Which jobs need a follow-up?',
    'What should I do next this week?',
    'Summarise where my current hunt stands.',
    'Draft a follow-up email for my oldest application.',
]

const toolLabels: Record<string, string> = {
    ListJobHunts: 'Looking at your hunts…',
    ListJobApplications: 'Looking at your jobs…',
    ShowJobApplication: 'Reading a job…',
}

const messages = ref<AssistantMessage[]>([...(props.conversation?.messages ?? [])])
const draft = ref('')
const isStreaming = ref(false)
const activity = ref<string | null>(null)
const isConfirmingDeletion = ref(false)
const scroller = ref<HTMLElement | null>(null)

const canSend = computed(() => props.isReady && !isStreaming.value && draft.value.trim() !== '')

watch(
    () => props.conversation,
    (conversation) => (messages.value = [...(conversation?.messages ?? [])]),
)

// Some reasoning models send their thinking inside <think> tags as part of the reply.
const visibleText = (content: string): string =>
    content
        .replace(/<think>[\s\S]*?(<\/think>|$)/g, '')
        .replace(/<\/think>/g, '')
        .trim()

const scrollToEnd = () => nextTick(() => scroller.value?.scrollTo({ top: scroller.value.scrollHeight }))

watch(() => messages.value.length, scrollToEnd, { immediate: true })

const xsrfToken = (): string => {
    const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : ''
}

const errorMessage = async (response: Response): Promise<string> => {
    const data = (await response.json().catch(() => null)) as { message?: string } | null

    return data?.message ?? 'The assistant could not answer. Please try again.'
}

const send = async (text = draft.value) => {
    const message = text.trim()

    if (!props.isReady || isStreaming.value || message === '') {
        return
    }

    messages.value.push({ id: crypto.randomUUID(), role: 'user', content: message, failed: false })
    messages.value.push({ id: crypto.randomUUID(), role: 'assistant', content: '', failed: false })
    const reply = messages.value[messages.value.length - 1]

    draft.value = ''
    isStreaming.value = true
    activity.value = 'Thinking…'

    let conversationId: string | null = props.conversation?.id ?? null

    try {
        const response = await fetch(assistantRoutes.messages.store.url(), {
            method: 'POST',
            headers: {
                Accept: 'text/event-stream',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({ message, conversation: conversationId }),
        })

        if (!response.ok || !response.body) {
            throw new Error(await errorMessage(response))
        }

        conversationId = response.headers.get('X-Conversation-Id') || conversationId

        const reader = response.body.pipeThrough(new TextDecoderStream()).getReader()
        let buffer = ''

        for (;;) {
            const { done, value } = await reader.read()

            if (done) {
                break
            }

            buffer += value
            const chunks = buffer.split('\n\n')
            buffer = chunks.pop() ?? ''

            for (const chunk of chunks) {
                const data = chunk.replace(/^data: /, '')

                if (data === '[DONE]') {
                    continue
                }

                const event = JSON.parse(data) as { type: string; delta?: string; tool_name?: string; message?: string }

                if (event.type === 'text_delta') {
                    reply.content += event.delta ?? ''
                    activity.value = null
                    scrollToEnd()
                } else if (event.type === 'tool_call') {
                    activity.value = toolLabels[event.tool_name ?? ''] ?? 'Working…'
                } else if (event.type === 'error') {
                    throw new Error(event.message ?? 'The assistant ran into a problem. Please try again.')
                }
            }
        }
    } catch (error) {
        reply.failed = true
        reply.content = error instanceof Error ? error.message : String(error)
    } finally {
        isStreaming.value = false
        activity.value = null
    }

    if (!reply.failed && conversationId) {
        router.visit(assistantRoutes.show.url(conversationId), {
            only: ['conversations', 'conversation'],
            preserveScroll: true,
            preserveState: true,
            replace: conversationId === props.conversation?.id,
        })
    }
}

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault()
        send()
    }
}

const deleteConversation = () => {
    if (!props.conversation) {
        return
    }

    router.delete(assistantRoutes.destroy.url(props.conversation.id), {
        onFinish: () => (isConfirmingDeletion.value = false),
    })
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-xl font-bold tracking-tight">Assistant</h1>
            <p class="text-sm text-muted-foreground">
                Ask about your job hunt, plan next steps and draft emails. It can read your jobs but not change them.
            </p>
        </div>

        <Card v-if="!isReady">
            <CardHeader>
                <CardTitle>Set Up the Assistant</CardTitle>
                <CardDescription>Add an API key for {{ provider }} to start using the assistant.</CardDescription>
            </CardHeader>
            <CardFooter class="justify-end">
                <Button as-child size="sm">
                    <Link :href="settingsRoutes.ai.edit.url()">Open AI Settings</Link>
                </Button>
            </CardFooter>
        </Card>

        <div v-else class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="flex flex-col gap-2">
                <Button as-child variant="outline" size="sm" class="justify-start">
                    <Link :href="assistantRoutes.show.url()">
                        <Plus />
                        New Conversation
                    </Link>
                </Button>
                <nav v-if="conversations.length" aria-label="Conversations" class="flex max-h-48 flex-col gap-0.5 overflow-y-auto lg:max-h-[60vh]">
                    <Link
                        v-for="item in conversations"
                        :key="item.id"
                        :href="assistantRoutes.show.url(item.id)"
                        :class="
                            cn(
                                'truncate rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted',
                                item.id === conversation?.id ? 'bg-muted font-medium' : 'text-muted-foreground',
                            )
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <section class="flex min-h-[60vh] flex-col rounded-xl border bg-card">
                <header v-if="conversation" class="flex items-center justify-between gap-4 border-b px-4 py-3">
                    <h2 class="truncate text-sm font-semibold">{{ conversation.title }}</h2>
                    <Button variant="ghost" size="sm" :disabled="isStreaming" @click="isConfirmingDeletion = true">
                        <Trash2 />
                        Delete
                    </Button>
                </header>

                <div ref="scroller" class="flex max-h-[60vh] flex-1 flex-col gap-4 overflow-y-auto p-4">
                    <div v-if="!messages.length" class="flex flex-1 flex-col items-center justify-center gap-4 py-10 text-center">
                        <p class="text-sm text-muted-foreground">Start with a question, or try one of these.</p>
                        <div class="flex max-w-xl flex-wrap justify-center gap-2">
                            <Button
                                v-for="suggestion in suggestions"
                                :key="suggestion"
                                variant="outline"
                                size="sm"
                                class="h-auto max-w-full py-1.5 whitespace-normal"
                                @click="send(suggestion)"
                            >
                                {{ suggestion }}
                            </Button>
                        </div>
                    </div>

                    <div v-for="message in messages" :key="message.id" :class="cn('flex', message.role === 'user' ? 'justify-end' : 'justify-start')">
                        <div
                            :class="
                                cn(
                                    'max-w-[85%] rounded-xl px-4 py-2.5 text-sm leading-relaxed break-words whitespace-pre-wrap',
                                    message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted',
                                    message.failed && 'border border-destructive/40 bg-destructive/10 text-destructive',
                                )
                            "
                        >
                            <template v-if="visibleText(message.content)">{{ visibleText(message.content) }}</template>
                            <span v-else class="inline-flex items-center gap-2 text-muted-foreground">
                                <LoaderCircle class="size-4 animate-spin" />
                                {{ activity ?? 'Thinking…' }}
                            </span>
                        </div>
                    </div>
                </div>

                <form class="flex items-end gap-2 border-t p-3" @submit.prevent="send()">
                    <Textarea
                        v-model="draft"
                        rows="2"
                        maxlength="4000"
                        aria-label="Message"
                        placeholder="Ask about your job hunt…"
                        class="max-h-48 min-h-11 resize-none"
                        @keydown="onKeydown"
                    />
                    <Button type="submit" size="icon" :disabled="!canSend" aria-label="Send">
                        <ArrowUp />
                    </Button>
                </form>
                <p class="px-4 pb-3 text-xs text-muted-foreground">
                    Answers come from {{ provider }} ({{ model }}) and can be wrong. Check before acting on them.
                </p>
            </section>
        </div>

        <ConfirmDialog
            v-model:open="isConfirmingDeletion"
            title="Delete This Conversation?"
            description="The conversation and its messages are deleted for good."
            confirm-label="Delete"
            @confirm="deleteConversation"
        />
    </div>
</template>
