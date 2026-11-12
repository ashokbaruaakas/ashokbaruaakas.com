<script setup lang="ts">
import { useElementSize } from '@vueuse/core'
import { computed, ref } from 'vue'
import ResumeDocument from '@/components/portfolio/ResumeDocument.vue'
import ResumeSheet from '@/components/portfolio/ResumeSheet.vue'
import type { Portfolio } from '@/types/portfolio'
import type { ResumeDesign } from '@/views/resume/templates'

defineProps<{
    portfolio: Portfolio
    design: ResumeDesign
}>()

const emit = defineEmits<{
    estimate: [pages: number]
}>()

const PAPER_WIDTH_PX = (210 * 96) / 25.4

const frame = ref<HTMLElement | null>(null)
const paper = ref<HTMLElement | null>(null)
const { width: frameWidth } = useElementSize(frame)
const { height: paperHeight } = useElementSize(paper, undefined, { box: 'border-box' })

const scale = computed(() => (frameWidth.value > 0 ? Math.min(1, frameWidth.value / PAPER_WIDTH_PX) : 0))
</script>

<template>
    <div ref="frame" class="relative overflow-hidden rounded-md bg-neutral-200" :style="{ height: `${paperHeight * scale}px` }">
        <div ref="paper" class="absolute top-0 left-0 origin-top-left" :style="{ width: `${PAPER_WIDTH_PX}px`, transform: `scale(${scale})` }">
            <ResumeSheet paginate @estimate="emit('estimate', $event)">
                <ResumeDocument :portfolio="portfolio" :design="design" />
            </ResumeSheet>
        </div>
    </div>
</template>
