<script setup lang="ts">
import { useResizeObserver } from '@vueuse/core'
import { computed, ref, watch } from 'vue'

defineProps<{
    showPageGuides: boolean
}>()

const emit = defineEmits<{
    estimate: [pages: number]
}>()

const PIXELS_PER_MM = 96 / 25.4
const PAPER_WIDTH_MM = 210
const PAPER_HEIGHT_MM = 297
const PAGE_MARGIN_MM = 12
const PAGE_CONTENT_HEIGHT_MM = PAPER_HEIGHT_MM - 2 * PAGE_MARGIN_MM

const sheet = ref<HTMLElement | null>(null)
const content = ref<HTMLElement | null>(null)
const contentHeightMm = ref(0)
const isPaperWidth = ref(false)

useResizeObserver(content, ([entry]) => {
    contentHeightMm.value = entry.contentRect.height / PIXELS_PER_MM
    isPaperWidth.value = (sheet.value?.offsetWidth ?? 0) >= PAPER_WIDTH_MM * PIXELS_PER_MM - 1
})

const estimatedPages = computed(() => Math.max(1, Math.ceil(contentHeightMm.value / PAGE_CONTENT_HEIGHT_MM)))

watch(
    [estimatedPages, isPaperWidth],
    ([pages, paperWidth]) => {
        if (paperWidth) {
            emit('estimate', pages)
        }
    },
    { immediate: true },
)

const pageBreaks = computed(() =>
    Array.from({ length: estimatedPages.value - 1 }, (_, index) => ({
        page: index + 2,
        top: `${PAGE_MARGIN_MM + (index + 1) * PAGE_CONTENT_HEIGHT_MM}mm`,
    })),
)
</script>

<template>
    <div>
        <div ref="sheet" class="resume-sheet" :style="isPaperWidth ? { minHeight: `${estimatedPages * PAPER_HEIGHT_MM}mm` } : undefined">
            <div ref="content">
                <slot />
            </div>
            <template v-if="showPageGuides && isPaperWidth">
                <div
                    v-for="pageBreak in pageBreaks"
                    :key="pageBreak.page"
                    class="resume-page-guide"
                    :style="{ top: pageBreak.top }"
                    aria-hidden="true"
                >
                    <span>Page {{ pageBreak.page }}</span>
                </div>
            </template>
        </div>
    </div>
</template>

<style>
.resume-sheet {
    position: relative;
    box-sizing: border-box;
    width: 210mm;
    max-width: 100%;
    margin: 0 auto;
    padding: 12mm;
    background: #fff;
    color: #000;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(0, 0, 0, 0.04);
}

.resume-page-guide {
    position: absolute;
    left: 0;
    right: 0;
    border-top: 1px dashed #94a3b8;
    pointer-events: none;
}

.resume-page-guide span {
    position: absolute;
    right: 4mm;
    top: 2px;
    font:
        7pt Arial,
        Helvetica,
        sans-serif;
    color: #94a3b8;
}

@media screen and (max-width: 600px) {
    .resume-sheet {
        padding: 16px;
    }
}

@media print {
    @page {
        size: A4;
        margin: 12mm;
    }

    html,
    body {
        background: #fff !important;
    }

    .resume-sheet {
        width: auto;
        max-width: none;
        min-height: 0 !important;
        padding: 0;
        box-shadow: none;
    }

    .resume-page-guide {
        display: none;
    }
}
</style>
