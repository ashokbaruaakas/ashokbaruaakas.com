<script setup lang="ts">
import SectionLayout from '@/components/app/SectionLayout.vue'
import { account as accountRoute } from '@/routes'
import account from '@/routes/account'
import type { NavGroup, NavItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const item = (title: string, href: string): NavItem => ({ title, href, isActive: () => currentPath.value === href })

const groups: NavGroup[] = [
    {
        items: [
            item('Overview', accountRoute.url()),
            item('Profile', account.profile.url()),
            item('Security', account.security.url()),
            item('Appearance', account.appearance.url()),
        ],
    },
]
</script>

<template>
    <SectionLayout label="Account" :groups="groups">
        <slot />
    </SectionLayout>
</template>
