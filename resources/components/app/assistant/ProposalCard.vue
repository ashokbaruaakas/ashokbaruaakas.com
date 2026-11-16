<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import assistantRoutes from '@/routes/assistant'
import type { AssistantFieldValue, AssistantProposal } from '@/types/assistant'
import { Link, router } from '@inertiajs/vue3'
import ResumeProposalPreview from './ResumeProposalPreview.vue'
import { diffWords } from './wordDiff'
import { ArrowRight, Check, Eye, Pencil, Plus, TriangleAlert, Undo2, X } from '@lucide/vue'
import { computed, ref } from 'vue'

const props = withDefaults(
    defineProps<{
        proposal: AssistantProposal
        reloads?: string[]
        loadsIntoEditor?: boolean
        isLoaded?: boolean
    }>(),
    { reloads: () => ['conversation', 'resumePdf'], loadsIntoEditor: false, isLoaded: false },
)

const emit = defineEmits<{
    load: [proposal: AssistantProposal]
    unload: []
}>()

const isSending = ref(false)
const isPreviewing = ref(false)
const isEditing = ref(false)
const drafts = ref<Record<string, string>>({})
const errors = ref<Record<string, string>>({})
const usedValues = ref<Record<string, AssistantFieldValue>>({})

const heading = computed(() => {
    const target = props.proposal.item ? `${props.proposal.section} · ${props.proposal.item}` : props.proposal.section

    return props.proposal.adds_item ? `New in ${target}` : target
})

const statusLabel = computed(() =>
    props.isLoaded
        ? 'In the Editor'
        : { pending: null, applied: props.proposal.adds_item ? 'Added' : 'Applied', dismissed: 'Dismissed' }[props.proposal.status],
)

const asText = (value: AssistantFieldValue): string => {
    if (Array.isArray(value)) {
        return value.join('\n')
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No'
    }

    return value === null ? '' : String(value)
}

const fields = computed(() =>
    props.proposal.fields.map((field) => ({
        ...field,
        parts: diffWords(
            props.proposal.adds_item ? '' : asText(field.before),
            asText(props.isLoaded && field.name in usedValues.value ? (usedValues.value[field.name] ?? null) : field.after),
        ),
    })),
)

const canEdit = computed(() => props.proposal.status === 'pending' && !props.isLoaded && props.proposal.fields.some(({ input }) => input))

const startEditing = () => {
    drafts.value = Object.fromEntries(props.proposal.fields.filter(({ input }) => input).map(({ name, after }) => [name, asText(after)]))
    errors.value = {}
    isEditing.value = true
}

const stopEditing = () => {
    isEditing.value = false
    errors.value = {}
}

const editedValue = (field: AssistantProposal['fields'][number]): AssistantProposal['fields'][number]['after'] => {
    const draft = drafts.value[field.name]

    if (!isEditing.value || draft === undefined) {
        return field.after
    }

    if (field.input === 'lines') {
        return draft
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
    }

    return draft.trim() === '' ? null : draft.trim()
}

const errorFor = (name: string): string | undefined =>
    errors.value[`changes.${name}`] ?? Object.entries(errors.value).find(([key]) => key.startsWith(`changes.${name}.`))?.[1]

const send = (route: { url: string }, data: Record<string, unknown> = {}, onSuccess?: () => void) => {
    router.post(route.url, data as Record<string, string>, {
        only: props.reloads,
        preserveScroll: true,
        preserveState: true,
        onStart: () => (isSending.value = true),
        onFinish: () => (isSending.value = false),
        onSuccess,
        onError: (pageErrors) => (errors.value = pageErrors),
    })
}

const apply = () =>
    send(
        assistantRoutes.proposals.apply(props.proposal.id),
        { force: props.proposal.is_stale, ...(isEditing.value ? { changes: drafts.value } : {}) },
        stopEditing,
    )

const useInEditor = () => {
    const used = props.proposal.fields.map((field) => ({ ...field, after: editedValue(field) }))

    usedValues.value = Object.fromEntries(used.map(({ name, after }) => [name, after]))
    emit('load', { ...props.proposal, fields: used })
    stopEditing()
}
</script>

<template>
    <div class="w-full max-w-[85%] rounded-xl border bg-background text-sm">
        <div class="flex items-start justify-between gap-3 border-b px-4 py-2.5">
            <div class="min-w-0">
                <p class="font-medium">{{ proposal.summary }}</p>
                <p class="truncate text-xs text-muted-foreground">
                    {{ heading }}
                </p>
            </div>
            <Badge v-if="statusLabel" :variant="proposal.status === 'applied' ? 'secondary' : 'outline'" class="shrink-0">{{ statusLabel }}</Badge>
        </div>

        <dl class="flex flex-col gap-3 px-4 py-3">
            <div v-for="field in fields" :key="field.name" class="flex flex-col gap-1">
                <dt class="text-xs font-medium text-muted-foreground">
                    <label v-if="isEditing && field.input" :for="`proposal-${proposal.id}-${field.name}`">{{ field.label }}</label>
                    <template v-else>{{ field.label }}</template>
                </dt>
                <dd v-if="isEditing && field.input" class="flex flex-col gap-1">
                    <Input
                        v-if="field.input === 'text'"
                        :id="`proposal-${proposal.id}-${field.name}`"
                        v-model="drafts[field.name]"
                        :aria-invalid="Boolean(errorFor(field.name))"
                    />
                    <Textarea
                        v-else
                        :id="`proposal-${proposal.id}-${field.name}`"
                        v-model="drafts[field.name]"
                        :rows="field.input === 'lines' ? 5 : 4"
                        :aria-invalid="Boolean(errorFor(field.name))"
                    />
                    <p v-if="field.input === 'lines'" class="text-xs text-muted-foreground">One per line.</p>
                    <InputError :message="errorFor(field.name)" />
                </dd>
                <dd v-else class="rounded-md bg-muted/50 px-2.5 py-1.5 leading-relaxed whitespace-pre-wrap">
                    <template v-if="field.parts.length">
                        <template v-for="(part, index) in field.parts" :key="index">
                            <del
                                v-if="part.type === 'removed'"
                                class="rounded-sm bg-red-500/15 text-red-700 decoration-red-700/50 dark:text-red-400 dark:decoration-red-400/50"
                                >{{ part.text }}</del
                            ><template v-else-if="part.type === 'added'"
                                ><span v-if="field.parts[index - 1]?.type === 'removed'" aria-hidden="true" class="text-muted-foreground"> → </span
                                ><ins class="rounded-sm bg-emerald-500/15 text-emerald-800 no-underline dark:text-emerald-300">{{
                                    part.text
                                }}</ins></template
                            ><span v-else>{{ part.text }}</span>
                        </template>
                    </template>
                    <span v-else class="text-muted-foreground italic">Empty</span>
                </dd>
            </div>
        </dl>

        <p
            v-if="proposal.is_stale && !loadsIntoEditor"
            class="mx-4 mb-3 flex items-start gap-2 rounded-md bg-amber-500/10 px-2.5 py-1.5 text-xs text-amber-700 dark:text-amber-400"
        >
            <TriangleAlert class="mt-0.5 size-3.5 shrink-0" />
            This changed since the assistant read it. Applying replaces your newer edit.
        </p>

        <p v-if="loadsIntoEditor && proposal.status === 'applied' && !isLoaded" class="border-t px-4 py-2 text-xs text-muted-foreground">
            Already applied and saved.
        </p>
        <div v-if="loadsIntoEditor && (proposal.status === 'pending' || isLoaded)" class="flex justify-end gap-2 border-t px-4 py-2">
            <Button type="button" v-if="isLoaded" variant="outline" size="sm" @click="emit('unload')">
                <Undo2 />
                Undo
            </Button>
            <template v-else-if="isEditing">
                <Button type="button" variant="ghost" size="sm" @click="stopEditing">Cancel</Button>
                <Button type="button" size="sm" @click="useInEditor">
                    <Check />
                    Use This
                </Button>
            </template>
            <template v-else>
                <Button
                    type="button"
                    v-if="proposal.status === 'pending'"
                    variant="ghost"
                    size="sm"
                    :disabled="isSending"
                    @click="send(assistantRoutes.proposals.dismiss(proposal.id))"
                >
                    <X />
                    Dismiss
                </Button>
                <Button type="button" v-if="canEdit" variant="outline" size="sm" @click="startEditing">
                    <Pencil />
                    Edit
                </Button>
                <Button type="button" size="sm" @click="useInEditor">
                    <Check />
                    Use This
                </Button>
            </template>
        </div>
        <div v-else-if="!loadsIntoEditor && proposal.status !== 'dismissed'" class="flex justify-end gap-2 border-t px-4 py-2">
            <template v-if="proposal.status === 'pending'">
                <Button type="button" v-if="proposal.preview_url" variant="outline" size="sm" @click="isPreviewing = true">
                    <Eye />
                    Preview
                </Button>
                <Button type="button" v-if="isEditing" variant="ghost" size="sm" :disabled="isSending" @click="stopEditing">Cancel</Button>
                <template v-else>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="isSending"
                        @click="send(assistantRoutes.proposals.dismiss(proposal.id))"
                    >
                        <X />
                        Dismiss
                    </Button>
                    <Button type="button" v-if="canEdit" variant="outline" size="sm" :disabled="isSending" @click="startEditing">
                        <Pencil />
                        Edit
                    </Button>
                </template>
                <Button type="button" size="sm" :disabled="isSending" @click="apply">
                    <Plus v-if="proposal.adds_item" />
                    <Check v-else />
                    {{ proposal.adds_item ? 'Add' : proposal.is_stale ? 'Apply Anyway' : 'Apply' }}
                </Button>
            </template>
            <template v-else>
                <Button type="button" variant="outline" size="sm" :disabled="isSending" @click="send(assistantRoutes.proposals.undo(proposal.id))">
                    <Undo2 />
                    Undo
                </Button>
                <Button v-if="proposal.link" size="sm" as-child>
                    <Link :href="proposal.link">
                        {{ proposal.link_label ?? 'Open Job' }}
                        <ArrowRight />
                    </Link>
                </Button>
            </template>
        </div>

        <ResumeProposalPreview v-if="proposal.preview_url" v-model:open="isPreviewing" :url="proposal.preview_url" :title="proposal.summary" />
    </div>
</template>
