<script setup lang="ts">
import SectionLayout from '@/components/administration/SectionLayout.vue'
import administrationPortfolio from '@/routes/administration/portfolio'
import type { NavGroup, NavItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const item = (title: string, href: string): NavItem => ({ title, href, isActive: () => currentPath.value === href })
const list = (title: string, key: string): NavItem => item(title, administrationPortfolio.lists.index.url(key))

const groups: NavGroup[] = [
    {
        label: 'About you',
        items: [
            item('Profile', administrationPortfolio.profile.edit.url()),
            item('Images', administrationPortfolio.media.index.url()),
            item('Headings and SEO', administrationPortfolio.copy.edit.url()),
            list('Hero stats', 'highlights'),
            list('Social links', 'social-links'),
        ],
    },
    {
        label: 'Work',
        items: [list('Skills', 'skills'), list('Projects', 'projects'), list('Case studies', 'case-studies'), list('Open source', 'open-source')],
    },
    {
        label: 'Background',
        items: [
            list('Experience', 'experience'),
            list('Education', 'education'),
            list('Languages', 'languages'),
            list('Testimonials', 'testimonials'),
        ],
    },
]
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
