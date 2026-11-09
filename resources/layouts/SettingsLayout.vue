<script setup lang="ts">
import SectionLayout from '@/components/app/SectionLayout.vue'
import settingsRoutes from '@/routes/settings'
import type { NavGroup, NavItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const item = (title: string, href: string): NavItem => ({ title, href, isActive: () => currentPath.value === href })

const groups: NavGroup[] = [
    {
        items: [
            item('AI', settingsRoutes.ai.edit.url()),
            item('Mail', settingsRoutes.mail.edit.url()),
            item('Authentication', settingsRoutes.authentication.edit.url()),
            item('Backup', settingsRoutes.backup.show.url()),
        ],
    },
]
</script>

<template>
    <SectionLayout label="Settings" description="AI, mail, sign-in and backups for this install." :groups="groups">
        <slot />
    </SectionLayout>
</template>
