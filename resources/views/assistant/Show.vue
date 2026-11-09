<script setup lang="ts">
import { encodeMentions, type JobMention, mentionLabel, messageParts } from '@/components/app/assistant/jobMentions'
import { parseMarkdown } from '@/components/app/assistant/markdown'
import CoverLetterCard from '@/components/app/assistant/CoverLetterCard.vue'
import MarkdownBlocks from '@/components/app/assistant/MarkdownBlocks.vue'
import ProposalCard from '@/components/app/assistant/ProposalCard.vue'
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { formatRelativeDate } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import assistantRoutes from '@/routes/assistant'
import jobHuntRoutes from '@/routes/job-hunt'
import settingsRoutes from '@/routes/settings'
import type { AssistantConversation, AssistantConversationSummary, AssistantJob, AssistantMessage } from '@/types/assistant'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowUp, AtSign, Briefcase, LoaderCircle, Pencil, Plus, Trash2 } from '@lucide/vue'
import { computed, nextTick, ref, watch } from 'vue'

const props = defineProps<{
    conversations: AssistantConversationSummary[]
    conversation: AssistantConversation | null
    jobs: AssistantJob[]
    newJob: AssistantJob | null
    isReady: boolean
    provider: string
    model: string
}>()

const focusJob = computed(() => (props.conversation ? props.conversation.job : props.newJob))

const suggestions = computed(() =>
    focusJob.value
        ? [
              'How well do I fit this job?',
              'What should I do next for this job?',
              'Write a cover letter for this job.',
              'Help me prepare for the next interview.',
          ]
        : [
              'Which jobs need a follow-up?',
              'What should I do next this week?',
              'Review my resume and suggest improvements.',
              'Draft a follow-up email for my oldest application.',
          ],
)

const toolLabels: Record<string, string> = {
    ListJobHunts: 'Looking at your hunts…',
    ListJobApplications: 'Looking at your jobs…',
    ShowJobApplication: 'Reading a job…',
    ReadPortfolio: 'Reading your portfolio…',
    ProposePortfolioChange: 'Preparing a change…',
    DraftCoverLetter: 'Writing the cover letter…',
}

const messages = ref<AssistantMessage[]>([...(props.conversation?.messages ?? [])])
const draft = ref('')
const isStreaming = ref(false)
const activity = ref<string | null>(null)
const isConfirmingDeletion = ref(false)
const scroller = ref<HTMLElement | null>(null)
const composer = ref<InstanceType<typeof Textarea> | null>(null)
const mentions = ref<JobMention[]>([])
const mentionQuery = ref<string | null>(null)
const mentionIndex = ref(0)

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

const openConversation = (id: unknown) => {
    if (typeof id === 'string' && id !== props.conversation?.id) {
        router.visit(assistantRoutes.show.url(id))
    }
}

const jobLink = (job: Pick<AssistantJob, 'id' | 'hunt_id'>): string => jobHuntRoutes.show.url(job.hunt_id, { query: { job: job.id } })

const clockTime: Intl.DateTimeFormatOptions = { hour: 'numeric', minute: '2-digit', hour12: true }
const shortDate: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }
const fullDate: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }

const formatClock = (value: string, options: Intl.DateTimeFormatOptions): string =>
    new Intl.DateTimeFormat(undefined, options)
        .formatToParts(new Date(value))
        .map(({ type, value: part }) => (type === 'dayPeriod' ? part.toUpperCase() : part))
        .join('')

const messageTime = (value: string): string =>
    formatClock(value, new Date(value).toDateString() === new Date().toDateString() ? clockTime : { ...shortDate, ...clockTime })

const jobById = (id: number): AssistantJob | undefined => props.jobs.find((job) => job.id === id)

const composerElement = (): HTMLTextAreaElement | null => (composer.value?.$el as HTMLTextAreaElement | undefined) ?? null

const mentionMatches = computed(() => {
    if (mentionQuery.value === null) {
        return []
    }

    const query = mentionQuery.value.toLowerCase()

    return props.jobs.filter(({ company, role }) => `${company} ${role}`.toLowerCase().includes(query)).slice(0, 6)
})

const isPickingMention = computed(() => mentionMatches.value.length > 0)

const findMentionQuery = () => {
    const element = composerElement()
    const typed = element ? element.value.slice(0, element.selectionStart) : draft.value
    const match = typed.match(/(?:^|\s)@([^\s@]{0,40})$/)

    mentionQuery.value = match ? match[1] : null
    mentionIndex.value = 0
}

const pickMention = async (job: AssistantJob) => {
    const element = composerElement()
    const caret = element?.selectionStart ?? draft.value.length
    const start = caret - (mentionQuery.value?.length ?? 0) - 1
    const label = mentionLabel(job, props.jobs)

    draft.value = `${draft.value.slice(0, start)}${label} ${draft.value.slice(caret)}`
    mentions.value = [...mentions.value.filter((mention) => mention.label !== label), { label, job }]
    mentionQuery.value = null

    await nextTick()
    element?.focus()
    element?.setSelectionRange(start + label.length + 1, start + label.length + 1)
}

const startMention = async () => {
    const element = composerElement()
    const caret = element?.selectionStart ?? draft.value.length
    const before = draft.value.slice(0, caret)
    const insert = before === '' || /\s$/.test(before) ? '@' : ' @'

    draft.value = `${before}${insert}${draft.value.slice(caret)}`

    await nextTick()
    element?.focus()
    element?.setSelectionRange(caret + insert.length, caret + insert.length)
    findMentionQuery()
}

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
    const message = encodeMentions(text.trim(), mentions.value)

    if (!props.isReady || isStreaming.value || message === '') {
        return
    }

    const sentAt = new Date().toISOString()

    messages.value.push({ id: crypto.randomUUID(), role: 'user', content: message, failed: false, created_at: sentAt })
    messages.value.push({ id: crypto.randomUUID(), role: 'assistant', content: '', failed: false, created_at: sentAt })
    const reply = messages.value[messages.value.length - 1]

    draft.value = ''
    mentions.value = []
    mentionQuery.value = null
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
            body: JSON.stringify({ message, conversation: conversationId, job: conversationId ? null : (props.newJob?.id ?? null) }),
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
    if (isPickingMention.value) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault()
            const step = event.key === 'ArrowDown' ? 1 : -1
            mentionIndex.value = (mentionIndex.value + step + mentionMatches.value.length) % mentionMatches.value.length

            return
        }

        if ((event.key === 'Enter' || event.key === 'Tab') && !event.isComposing) {
            event.preventDefault()
            pickMention(mentionMatches.value[mentionIndex.value])

            return
        }

        if (event.key === 'Escape') {
            event.preventDefault()
            mentionQuery.value = null

            return
        }
    }

    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault()
        send()
    }
}

const isRenaming = ref(false)
const titleInput = ref<InstanceType<typeof Input> | null>(null)
const renameForm = useForm({ title: '' })

const startRenaming = async () => {
    if (!props.conversation) {
        return
    }

    renameForm.title = props.conversation.title
    renameForm.clearErrors()
    isRenaming.value = true

    await nextTick()
    const element = titleInput.value?.$el as HTMLInputElement | undefined
    element?.focus()
    element?.select()
}

const rename = () => {
    if (!props.conversation) {
        return
    }

    if (renameForm.title.trim() === props.conversation.title) {
        isRenaming.value = false

        return
    }

    renameForm.put(assistantRoutes.update.url(props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => (isRenaming.value = false),
    })
}

watch(
    () => props.conversation?.id,
    () => (isRenaming.value = false),
)

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
                Work on your portfolio and job hunt together: it suggests changes you apply with one click, and drafts cover letters and emails.
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
            <aside class="flex gap-2 lg:flex-col">
                <Select v-if="conversations.length" :model-value="conversation?.id" @update:model-value="openConversation">
                    <SelectTrigger size="sm" class="min-w-0 flex-1 lg:hidden" aria-label="Conversation">
                        <SelectValue placeholder="Open a conversation" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="item in conversations" :key="item.id" :value="item.id">{{ item.title }}</SelectItem>
                    </SelectContent>
                </Select>
                <Button as-child variant="outline" size="sm" class="shrink-0 justify-start">
                    <Link :href="assistantRoutes.show.url()">
                        <Plus />
                        <span :class="{ 'sr-only sm:not-sr-only': conversations.length > 0 }">New Conversation</span>
                    </Link>
                </Button>
                <nav v-if="conversations.length" aria-label="Conversations" class="hidden max-h-[60vh] flex-col gap-0.5 overflow-y-auto lg:flex">
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
                        <span class="block truncate">{{ item.title }}</span>
                        <span class="flex gap-1.5 text-xs font-normal text-muted-foreground">
                            <span v-if="item.job" class="truncate">{{ item.job.company }}</span>
                            <span v-if="item.job && item.updated_at" aria-hidden="true">·</span>
                            <time
                                v-if="item.updated_at"
                                :datetime="item.updated_at"
                                :title="formatClock(item.updated_at, { ...shortDate, ...clockTime })"
                                class="shrink-0"
                            >
                                {{ formatRelativeDate(item.updated_at) }}
                            </time>
                        </span>
                    </Link>
                </nav>
            </aside>

            <section class="flex min-h-[60vh] flex-col rounded-xl border bg-card">
                <header v-if="conversation" class="flex min-h-14 items-center justify-between gap-4 border-b px-4 py-2">
                    <form v-if="isRenaming" class="flex flex-1 flex-col gap-1" @submit.prevent="rename">
                        <div class="flex items-center gap-2">
                            <Input
                                ref="titleInput"
                                v-model="renameForm.title"
                                maxlength="120"
                                required
                                aria-label="Conversation title"
                                :aria-invalid="Boolean(renameForm.errors.title)"
                                class="h-8"
                                @keydown.esc.prevent="isRenaming = false"
                            />
                            <Button type="button" variant="ghost" size="sm" @click="isRenaming = false">Cancel</Button>
                            <Button type="submit" size="sm" :disabled="renameForm.processing || !renameForm.title.trim()">Save</Button>
                        </div>
                        <InputError :message="renameForm.errors.title" />
                    </form>
                    <template v-else>
                        <h2 class="min-w-0 truncate text-sm font-semibold" @dblclick="startRenaming">{{ conversation.title }}</h2>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button variant="ghost" size="sm" :disabled="isStreaming" @click="startRenaming">
                                <Pencil />
                                <span class="sr-only sm:not-sr-only">Rename</span>
                            </Button>
                            <Button variant="ghost" size="sm" :disabled="isStreaming" @click="isConfirmingDeletion = true">
                                <Trash2 />
                                <span class="sr-only sm:not-sr-only">Delete</span>
                            </Button>
                        </div>
                    </template>
                </header>

                <div v-if="focusJob" class="flex items-center justify-between gap-3 border-b bg-muted/40 px-4 py-2 text-sm">
                    <p class="flex min-w-0 items-center gap-2">
                        <Briefcase class="size-4 shrink-0 text-muted-foreground" />
                        <span class="truncate">
                            About <span class="font-medium">{{ focusJob.company }}</span> — {{ focusJob.role }}
                            <span class="text-muted-foreground">· {{ focusJob.hunt }}</span>
                        </span>
                    </p>
                    <Button as-child variant="ghost" size="sm" class="shrink-0">
                        <Link :href="jobLink(focusJob)">Open Job</Link>
                    </Button>
                </div>

                <div ref="scroller" class="flex max-h-[60vh] flex-1 flex-col gap-4 overflow-y-auto p-4">
                    <div v-if="!messages.length" class="flex flex-1 flex-col items-center justify-center gap-4 py-10 text-center">
                        <p class="text-sm text-muted-foreground">
                            {{
                                focusJob
                                    ? 'Ask anything about this job, or try one of these.'
                                    : 'Start with a question, or try one of these. Type @ to bring in a job.'
                            }}
                        </p>
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

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        :class="cn('flex flex-col gap-1', message.role === 'user' ? 'items-end' : 'items-start')"
                    >
                        <div
                            v-if="visibleText(message.content) || !(message.proposals?.length || message.coverLetters?.length)"
                            :class="
                                cn(
                                    'max-w-[85%] min-w-0 rounded-xl px-4 py-2.5 text-sm leading-relaxed break-words',
                                    message.role === 'user' ? 'bg-primary whitespace-pre-wrap text-primary-foreground' : 'bg-muted',
                                    message.failed && 'border border-destructive/40 bg-destructive/10 text-destructive',
                                )
                            "
                        >
                            <MarkdownBlocks
                                v-if="message.role === 'assistant' && !message.failed && visibleText(message.content)"
                                :blocks="parseMarkdown(visibleText(message.content))"
                                :jobs="jobs"
                            />
                            <template v-else-if="visibleText(message.content)">
                                <template v-for="(part, index) in messageParts(visibleText(message.content))" :key="index">
                                    <template v-if="'text' in part">{{ part.text }}</template>
                                    <Link
                                        v-else-if="jobById(part.jobId)"
                                        :href="jobLink(jobById(part.jobId)!)"
                                        :class="
                                            cn(
                                                'rounded px-1 font-medium underline-offset-2 hover:underline',
                                                message.role === 'user' ? 'bg-primary-foreground/15' : 'bg-background',
                                            )
                                        "
                                        >@{{ part.label }}</Link
                                    >
                                    <span v-else class="font-medium">@{{ part.label }}</span>
                                </template>
                            </template>
                            <span v-else class="inline-flex items-center gap-2 text-muted-foreground">
                                <LoaderCircle class="size-4 animate-spin" />
                                {{ activity ?? 'Thinking…' }}
                            </span>
                        </div>
                        <time
                            v-if="message.created_at && visibleText(message.content)"
                            :datetime="message.created_at"
                            :title="formatClock(message.created_at, { ...fullDate, ...clockTime })"
                            class="px-1 text-xs text-muted-foreground"
                        >
                            {{ messageTime(message.created_at) }}
                        </time>
                        <ProposalCard v-for="proposal in message.proposals" :key="`proposal-${proposal.id}`" :proposal="proposal" />
                        <CoverLetterCard v-for="letter in message.coverLetters" :key="`letter-${letter.id}`" :letter="letter" />
                    </div>
                </div>

                <form class="relative flex items-end gap-2 border-t p-3" @submit.prevent="send()">
                    <ul
                        v-if="isPickingMention"
                        id="job-mentions"
                        role="listbox"
                        aria-label="Jobs"
                        class="absolute right-3 bottom-full left-3 z-10 mb-1 grid max-w-md gap-0.5 rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
                    >
                        <li
                            v-for="(job, index) in mentionMatches"
                            :id="`job-mention-${job.id}`"
                            :key="job.id"
                            role="option"
                            :aria-selected="index === mentionIndex"
                            :class="cn('cursor-pointer rounded-md px-3 py-1.5 text-sm', index === mentionIndex && 'bg-muted')"
                            @mousedown.prevent="pickMention(job)"
                            @mouseenter="mentionIndex = index"
                        >
                            <span class="font-medium">{{ job.company }}</span>
                            <span class="text-muted-foreground"> — {{ job.role }}</span>
                            <span class="block truncate text-xs text-muted-foreground">{{ job.hunt }}</span>
                        </li>
                    </ul>
                    <Textarea
                        ref="composer"
                        v-model="draft"
                        rows="2"
                        maxlength="4000"
                        aria-label="Message"
                        aria-autocomplete="list"
                        :aria-expanded="isPickingMention"
                        :aria-controls="isPickingMention ? 'job-mentions' : undefined"
                        :aria-activedescendant="isPickingMention ? `job-mention-${mentionMatches[mentionIndex]?.id}` : undefined"
                        :placeholder="
                            focusJob ? `Ask about ${focusJob.company}…` : 'Ask about your portfolio or job hunt, or type @ to mention a job…'
                        "
                        class="max-h-48 min-h-11 resize-none"
                        @keydown="onKeydown"
                        @input="findMentionQuery"
                        @click="findMentionQuery"
                        @blur="mentionQuery = null"
                    />
                    <Button
                        v-if="jobs.length"
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Mention a Job"
                        title="Mention a Job"
                        @click="startMention"
                    >
                        <AtSign />
                    </Button>
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
