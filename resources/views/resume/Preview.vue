<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import ResumeSheet from '@/components/portfolio/ResumeSheet.vue'
import type { Portfolio } from '@/types/portfolio'
import { resolveResumeTemplate, TEMPLATE_ESTIMATE_MESSAGE } from './templates'

const { portfolio, template } = defineProps<{
    portfolio: Portfolio
    template: string
}>()

const templateComponent = computed(() => resolveResumeTemplate(template))

const reportEstimate = (pages: number) => {
    window.parent.postMessage({ type: TEMPLATE_ESTIMATE_MESSAGE, template, pages }, window.location.origin)
}
</script>

<template>
    <Head title="Resume Preview" />

    <div class="min-h-screen bg-neutral-200 py-[5mm]">
        <ResumeSheet show-page-guides @estimate="reportEstimate">
            <component :is="templateComponent" :portfolio="portfolio" />
        </ResumeSheet>
    </div>
</template>
