<script setup lang="ts">
import { useResizeObserver } from '@vueuse/core'
import { computed, onMounted, ref } from 'vue'

const { paginate = false } = defineProps<{
    paginate?: boolean
}>()

const emit = defineEmits<{
    estimate: [pages: number, lastPageFill: number]
}>()

const PIXELS_PER_MM = 96 / 25.4
const PAGE_HEIGHT_MM = 297
const PAGE_MARGIN_MM = 12
const PAGE_GAP_MM = 8
const PAGE_PITCH_MM = PAGE_HEIGHT_MM + PAGE_GAP_MM
const PAGE_CONTENT_HEIGHT_MM = PAGE_HEIGHT_MM - 2 * PAGE_MARGIN_MM
const UNBREAKABLE_TAGS = new Set(['P', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'svg'])

const content = ref<HTMLElement | null>(null)
const pages = ref(1)

const isUnbreakable = (element: Element): boolean =>
    UNBREAKABLE_TAGS.has(element.tagName) || element.children.length === 0 || getComputedStyle(element).breakInside === 'avoid'

/**
 * Lay the content out on A4 pages the way Chrome prints it: a block that does
 * not fit at the bottom of a page moves to the next one, and a heading that must
 * not end a page moves with it. Pushes are added as top padding so they never
 * collapse with neighbouring margins.
 */
const layOutPages = () => {
    const root = content.value

    if (!root) {
        return
    }

    for (const pushed of root.querySelectorAll<HTMLElement>('[data-page-push]')) {
        pushed.style.paddingTop = pushed.dataset.pagePush ?? ''
        delete pushed.dataset.pagePush
    }

    const scale = root.offsetHeight > 0 ? root.getBoundingClientRect().height / root.offsetHeight : 1
    const offsetOf = (y: number) => (y - root.getBoundingClientRect().top) / scale / PIXELS_PER_MM - PAGE_MARGIN_MM
    const topOf = (element: Element) => offsetOf(element.getBoundingClientRect().top)
    const bottomOf = (element: Element) => offsetOf(element.getBoundingClientRect().bottom)

    const pushToNextPage = (element: HTMLElement, page: number) => {
        let target = element

        for (let previous = element.previousElementSibling; previous instanceof HTMLElement; previous = previous.previousElementSibling) {
            if (getComputedStyle(previous).breakAfter !== 'avoid') {
                break
            }

            target = previous
        }

        const shift = (page + 1) * PAGE_PITCH_MM - topOf(target)

        if (shift <= 0) {
            return
        }

        target.dataset.pagePush = target.style.paddingTop
        target.style.paddingTop = `${parseFloat(getComputedStyle(target).paddingTop) + shift * PIXELS_PER_MM}px`
    }

    const place = (element: HTMLElement) => {
        const top = topOf(element)
        const bottom = bottomOf(element)
        const page = Math.floor(top / PAGE_PITCH_MM)
        const pageEnd = page * PAGE_PITCH_MM + PAGE_CONTENT_HEIGHT_MM

        if (bottom - top > PAGE_CONTENT_HEIGHT_MM && element.children.length > 0) {
            walk(element)
        } else if (top >= pageEnd || bottom > pageEnd + 0.5) {
            pushToNextPage(element, page)
        }
    }

    const walk = (parent: Element) => {
        for (const child of Array.from(parent.children)) {
            if (!(child instanceof HTMLElement)) {
                continue
            }

            if (isUnbreakable(child)) {
                place(child)
            } else {
                walk(child)
            }
        }
    }

    walk(root)

    const contentBottom = Math.max(0, bottomOf(root) - 0.5)

    pages.value = Math.max(1, Math.floor(contentBottom / PAGE_PITCH_MM) + 1)
    emit('estimate', pages.value, Math.min(1, Math.max(0, (contentBottom - (pages.value - 1) * PAGE_PITCH_MM) / PAGE_CONTENT_HEIGHT_MM)))
}

let frame: number | null = null

const scheduleLayout = () => {
    if (!paginate || frame !== null) {
        return
    }

    frame = requestAnimationFrame(() => {
        frame = null
        layOutPages()
    })
}

useResizeObserver(content, scheduleLayout)

onMounted(() => {
    void document.fonts?.ready.then(scheduleLayout)
})

const pageOffsets = computed(() => Array.from({ length: pages.value }, (_, index) => `${index * PAGE_PITCH_MM}mm`))
</script>

<template>
    <div v-if="paginate" class="resume-pages" :style="{ height: `${pages * PAGE_PITCH_MM - PAGE_GAP_MM}mm` }">
        <div v-for="(top, index) in pageOffsets" :key="index" class="resume-page" :style="{ top }" aria-hidden="true">
            <span>Page {{ index + 1 }}</span>
        </div>
        <div ref="content" class="resume-pages-content">
            <slot />
        </div>
    </div>
    <div v-else class="resume-sheet">
        <slot />
    </div>
</template>

<style>
.resume-sheet {
    box-sizing: border-box;
    width: 210mm;
    max-width: 100%;
    margin: 0 auto;
    padding: 12mm;
    background: #fff;
    color: #000;
}

.resume-pages {
    position: relative;
    width: 210mm;
    color: #000;
}

.resume-page {
    position: absolute;
    left: 0;
    width: 210mm;
    height: 297mm;
    background: #fff;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(0, 0, 0, 0.04);
}

.resume-page span {
    position: absolute;
    right: 4mm;
    bottom: 3mm;
    font:
        7pt Arial,
        Helvetica,
        sans-serif;
    color: #94a3b8;
}

.resume-pages-content {
    position: relative;
    box-sizing: border-box;
    padding: 12mm 12mm 0;
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
        padding: 0;
    }
}
</style>
