<script setup lang="ts">
import SectionLayout from '@/components/administration/SectionLayout.vue'
import administrationSettings from '@/routes/administration/settings'
import type { NavGroup, NavItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const item = (title: string, href: string): NavItem => ({ title, href, isActive: () => currentPath.value === href })

const groups: NavGroup[] = [
    {
        items: [
            item('General', administrationSettings.general.edit.url()),
            item('Authentication', administrationSettings.authentication.edit.url()),
            item('Mail', administrationSettings.mail.edit.url()),
        ],
    },
]
</script>

<template>
    <SectionLayout label="Settings" description="Change how the application looks and behaves for everyone." :groups="groups">
        <slot />
    </SectionLayout>
</template>
