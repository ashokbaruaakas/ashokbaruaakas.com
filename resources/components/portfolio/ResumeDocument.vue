<script setup lang="ts">
import { computed } from 'vue'
import type { Portfolio } from '@/types/portfolio'
import { resolveResumeTemplate, resumeFonts, type ResumeDesign } from '@/views/resume/templates'

const { portfolio, design } = defineProps<{
    portfolio: Portfolio
    design: ResumeDesign
}>()

const templateComponent = computed(() => resolveResumeTemplate(design.template))

const style = computed(() => {
    const font = resumeFonts[design.font] ?? resumeFonts.arial!

    return { '--resume-accent': design.accentColor, '--resume-font': font.body, '--resume-name-font': font.name }
})
</script>

<template>
    <div :style="style">
        <component :is="templateComponent" :portfolio="portfolio" />
    </div>
</template>
