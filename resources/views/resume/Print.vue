<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import ResumeSheet from '@/components/portfolio/ResumeSheet.vue'
import type { Portfolio } from '@/types/portfolio'
import { resolveResumeTemplate } from './templates'

const { portfolio, template } = defineProps<{
    portfolio: Portfolio
    template: string
}>()

const templateComponent = computed(() => resolveResumeTemplate(template))
</script>

<template>
    <Head :title="`${portfolio.name} — Resume`" />

    <ResumeSheet :show-page-guides="false">
        <component :is="templateComponent" :portfolio="portfolio" />
    </ResumeSheet>
</template>
