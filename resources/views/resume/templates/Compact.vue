<script setup lang="ts">
import type { Portfolio } from '@/types/portfolio'
import Classic from './Classic.vue'

defineProps<{
    portfolio: Portfolio
}>()
</script>

<template>
    <Classic :portfolio="portfolio" class="resume-compact" />
</template>

<style>
.resume-classic.resume-compact .resume-header {
    padding-bottom: 4mm;
}

.resume-classic.resume-compact .resume-header h1 {
    font-size: 18pt;
    margin-bottom: 1px;
}

.resume-classic.resume-compact .resume-title {
    font-size: 9.5pt;
    margin-bottom: 4px;
}

.resume-classic.resume-compact .resume-contact-row {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 1px 12px;
    font-size: 7.5pt;
}

.resume-classic.resume-compact .resume-section {
    margin-bottom: 8px;
}

.resume-classic.resume-compact .resume-section-title {
    font-size: 9.5pt;
    padding-bottom: 2px;
    border-bottom-width: 1.5px;
    margin-bottom: 4px;
}

.resume-classic.resume-compact .resume-summary {
    font-size: 8.5pt;
    line-height: 1.4;
}

.resume-classic.resume-compact .resume-skill-group {
    margin-bottom: 1px;
    font-size: 8.5pt;
    line-height: 1.4;
}

.resume-classic.resume-compact .resume-skill-group h3,
.resume-classic.resume-compact .resume-skill-group p {
    display: inline;
    font-size: inherit;
    line-height: inherit;
}

.resume-classic.resume-compact .resume-inline-sep {
    display: inline;
}

.resume-classic.resume-compact .resume-exp-item {
    margin-bottom: 6px;
}

.resume-classic.resume-compact .resume-role {
    font-size: 9pt;
}

.resume-classic.resume-compact .resume-sep,
.resume-classic.resume-compact .resume-company {
    font-size: 8.5pt;
}

.resume-classic.resume-compact .resume-date {
    font-size: 7.5pt;
}

.resume-classic.resume-compact .resume-exp-context {
    font-size: 8pt;
    margin: 0 0 1px;
}

.resume-classic.resume-compact .resume-exp-bullets li {
    font-size: 8pt;
    padding-left: 11px;
    margin-bottom: 0;
}

.resume-classic.resume-compact .resume-proj-item {
    margin-bottom: 5px;
}

.resume-classic.resume-compact .resume-proj-name {
    font-size: 8.5pt;
}

.resume-classic.resume-compact .resume-proj-link {
    font-size: 7.5pt;
}

.resume-classic.resume-compact .resume-proj-desc,
.resume-classic.resume-compact .resume-school {
    font-size: 8pt;
}

.resume-classic.resume-compact .resume-edu-item {
    margin-bottom: 3px;
}

.resume-classic.resume-compact .resume-edu-item .resume-date {
    display: inline;
}

.resume-classic.resume-compact .resume-degree {
    font-size: 8.5pt;
}

.resume-classic.resume-compact .resume-lang-list {
    font-size: 8.5pt;
}
</style>
