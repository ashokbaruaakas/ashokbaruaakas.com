<script setup lang="ts">
import ResumePreviewPane from '@/components/app/portfolio/ResumePreviewPane.vue'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogDescription, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog'
import type { Portfolio } from '@/types/portfolio'
import type { ResumeDesign } from '@/views/resume/templates'
import { useHttp } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

type Preview = {
    portfolio: Portfolio
    design: ResumeDesign
    ats: { score: number; jobMatchScore: number | null; matchedKeywords: string[] | null; missingKeywords: string[] | null }
}

const props = defineProps<{
    url: string
    title: string
}>()

const open = defineModel<boolean>('open', { required: true })

const request = useHttp<Record<string, never>, Preview>()
const preview = ref<Preview | null>(null)
const estimatedPages = ref<number | null>(null)

watch(open, async (isOpen) => {
    if (!isOpen) {
        return
    }

    preview.value = await request.get(props.url).catch(() => null)
})
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>How the resume looks once you apply this. The built PDF can differ slightly.</DialogDescription>
            </DialogHeader>

            <p v-if="request.hasErrors || (!request.processing && !preview)" class="text-sm text-destructive">The preview couldn't be loaded.</p>

            <template v-else-if="preview">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <span v-if="estimatedPages !== null" class="tabular-nums"
                        >About {{ estimatedPages }} {{ estimatedPages === 1 ? 'page' : 'pages' }} on A4</span
                    >
                    <span class="tabular-nums">ATS score {{ preview.ats.score }} / 100</span>
                    <span v-if="preview.ats.jobMatchScore !== null" class="tabular-nums">Job match {{ preview.ats.jobMatchScore }} / 100</span>
                </div>
                <div v-if="preview.ats.missingKeywords?.length" class="grid gap-1.5 text-sm">
                    <span class="text-muted-foreground">Job keywords the resume doesn't mention</span>
                    <div class="flex flex-wrap gap-1.5">
                        <Badge v-for="keyword in preview.ats.missingKeywords" :key="keyword" variant="outline">{{ keyword }}</Badge>
                    </div>
                </div>
                <ResumePreviewPane :portfolio="preview.portfolio" :design="preview.design" @estimate="estimatedPages = $event" />
            </template>

            <div v-else class="aspect-[210/297] w-full animate-pulse rounded-md bg-muted" />
        </DialogScrollContent>
    </Dialog>
</template>
