<script setup lang="ts">
import type { Inline } from '@/components/app/assistant/markdown'
import jobHuntRoutes from '@/routes/job-hunt'
import type { AssistantJob } from '@/types/assistant'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{
    nodes: Inline[]
    jobs: AssistantJob[]
}>()

const jobLink = (jobId: number): string | null => {
    const job = props.jobs.find(({ id }) => id === jobId)

    return job ? jobHuntRoutes.show.url(job.hunt_id, { query: { job: job.id } }) : null
}
</script>

<template>
    <template v-for="(node, index) in nodes" :key="index">
        <template v-if="node.type === 'text'">{{ node.text }}</template>
        <br v-else-if="node.type === 'break'" />
        <code v-else-if="node.type === 'code'" class="rounded bg-background px-1 py-0.5 font-mono text-[0.85em]">{{ node.text }}</code>
        <strong v-else-if="node.type === 'strong'" class="font-semibold"><MarkdownInline :nodes="node.children" :jobs="jobs" /></strong>
        <em v-else-if="node.type === 'em'"><MarkdownInline :nodes="node.children" :jobs="jobs" /></em>
        <del v-else-if="node.type === 'del'"><MarkdownInline :nodes="node.children" :jobs="jobs" /></del>
        <a
            v-else-if="node.type === 'link'"
            :href="node.href"
            target="_blank"
            rel="noopener noreferrer"
            class="underline underline-offset-2 hover:text-foreground/80"
            ><MarkdownInline :nodes="node.children" :jobs="jobs"
        /></a>
        <Link
            v-else-if="node.type === 'mention' && jobLink(node.jobId)"
            :href="jobLink(node.jobId)!"
            class="rounded bg-background px-1 font-medium underline-offset-2 hover:underline"
            >@{{ node.label }}</Link
        >
        <span v-else-if="node.type === 'mention'" class="font-medium">@{{ node.label }}</span>
    </template>
</template>
