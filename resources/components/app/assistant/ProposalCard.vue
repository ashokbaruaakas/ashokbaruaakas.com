<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import assistantRoutes from '@/routes/assistant'
import type { AssistantFieldValue, AssistantProposal } from '@/types/assistant'
import { router } from '@inertiajs/vue3'
import { Check, TriangleAlert, Undo2, X } from '@lucide/vue'
import { computed, ref } from 'vue'

const props = defineProps<{
    proposal: AssistantProposal
}>()

const isSending = ref(false)

const heading = computed(() => {
    const target = props.proposal.item ? `${props.proposal.section} · ${props.proposal.item}` : props.proposal.section

    return props.proposal.adds_item ? `New in ${target}` : target
})

const statusLabel = computed(() => ({ pending: null, applied: 'Applied', dismissed: 'Dismissed' })[props.proposal.status])

const lines = (value: AssistantFieldValue): string[] => {
    if (value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
        return []
    }

    if (Array.isArray(value)) {
        return value
    }

    if (typeof value === 'boolean') {
        return [value ? 'Yes' : 'No']
    }

    return [String(value)]
}

const send = (route: { url: string }) => {
    router.post(
        route.url,
        {},
        {
            only: ['conversation'],
            preserveScroll: true,
            preserveState: true,
            onStart: () => (isSending.value = true),
            onFinish: () => (isSending.value = false),
        },
    )
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
            <div v-for="field in proposal.fields" :key="field.name" class="flex flex-col gap-1">
                <dt class="text-xs font-medium text-muted-foreground">
                    {{ field.label }}
                </dt>
                <dd class="flex flex-col gap-1">
                    <div
                        v-if="!proposal.adds_item"
                        class="rounded-md bg-destructive/5 px-2.5 py-1.5 whitespace-pre-wrap text-muted-foreground line-through decoration-destructive/40"
                    >
                        <template v-if="lines(field.before).length">
                            <span v-for="(line, index) in lines(field.before)" :key="index" class="block">{{ line }}</span>
                        </template>
                        <span v-else class="italic no-underline">Empty</span>
                    </div>
                    <div class="rounded-md bg-emerald-500/10 px-2.5 py-1.5 whitespace-pre-wrap">
                        <template v-if="lines(field.after).length">
                            <span v-for="(line, index) in lines(field.after)" :key="index" class="block">{{ line }}</span>
                        </template>
                        <span v-else class="text-muted-foreground italic">Empty</span>
                    </div>
                </dd>
            </div>
        </dl>

        <p
            v-if="proposal.is_stale"
            class="mx-4 mb-3 flex items-start gap-2 rounded-md bg-amber-500/10 px-2.5 py-1.5 text-xs text-amber-700 dark:text-amber-400"
        >
            <TriangleAlert class="mt-0.5 size-3.5 shrink-0" />
            This changed since the assistant read it. Applying replaces your newer edit.
        </p>

        <div v-if="proposal.status !== 'dismissed'" class="flex justify-end gap-2 border-t px-4 py-2">
            <template v-if="proposal.status === 'pending'">
                <Button variant="ghost" size="sm" :disabled="isSending" @click="send(assistantRoutes.proposals.dismiss(proposal.id))">
                    <X />
                    Dismiss
                </Button>
                <Button size="sm" :disabled="isSending" @click="send(assistantRoutes.proposals.apply(proposal.id))">
                    <Check />
                    {{ proposal.is_stale ? 'Apply Anyway' : 'Apply' }}
                </Button>
            </template>
            <Button v-else variant="outline" size="sm" :disabled="isSending" @click="send(assistantRoutes.proposals.undo(proposal.id))">
                <Undo2 />
                Undo
            </Button>
        </div>
    </div>
</template>
