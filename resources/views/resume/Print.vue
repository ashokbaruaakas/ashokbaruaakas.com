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
</script>

<template>
    <Head :title="`${portfolio.name} — Resume`" />

    <ResumeSheet :show-page-guides="false">
        <ResumeDocument :portfolio="portfolio" :design="design" />
    </ResumeSheet>
</template>
