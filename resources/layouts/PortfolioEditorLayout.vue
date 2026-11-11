<script setup lang="ts">
import SectionLayout from '@/components/app/SectionLayout.vue'
import { portfolioSectionGroups } from '@/components/app/portfolio/sections'
import type { NavGroup } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const groups: NavGroup[] = portfolioSectionGroups.map(({ label, items }) => ({
    label,
    items: items.map(({ title, href }) => ({ title, href, isActive: () => currentPath.value === href })),
}))
</script>

<template>
    <SectionLayout
        label="Portfolio"
        description="Edit what visitors see. Public pages can take up to 5 minutes to show your changes."
        :groups="groups"
    >
        <slot />
    </SectionLayout>
</template>
