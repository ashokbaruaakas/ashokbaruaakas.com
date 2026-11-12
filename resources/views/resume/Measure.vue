<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import ResumeDocument from '@/components/portfolio/ResumeDocument.vue'
import ResumeSheet from '@/components/portfolio/ResumeSheet.vue'
import type { Portfolio } from '@/types/portfolio'
import type { ResumeDesign } from './templates'

defineProps<{
    portfolio: Portfolio
    design: ResumeDesign
}>()

const record = (pages: number, lastPageFill: number) => {
    document.documentElement.dataset.resumePages = String(pages)
    document.documentElement.dataset.resumeLastPageFill = lastPageFill.toFixed(3)
}
</script>

<template>
    <Head :title="`${portfolio.name} — Resume`" />

    <ResumeSheet paginate @estimate="record">
        <ResumeDocument :portfolio="portfolio" :design="design" />
    </ResumeSheet>
</template>
