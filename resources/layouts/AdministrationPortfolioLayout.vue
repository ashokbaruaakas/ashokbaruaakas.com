<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { isNavItemActive } from '@/lib/helpers'
import administrationPortfolio from '@/routes/administration/portfolio'
import type { NavItem } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted, useTemplateRef } from 'vue'

const page = usePage()
const navigation = useTemplateRef<HTMLElement>('navigation')
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

const item = (title: string, href: string): NavItem => ({ title, href, isActive: () => currentPath.value === href })
const list = (title: string, key: string): NavItem => item(title, administrationPortfolio.lists.index.url(key))

const sections: NavItem[] = [
    item('Profile', administrationPortfolio.profile.edit.url()),
    item('Images', administrationPortfolio.media.index.url()),
    item('Headings and SEO', administrationPortfolio.copy.edit.url()),
    list('Hero stats', 'highlights'),
    list('Social links', 'social-links'),
    list('Skills', 'skills'),
    list('Projects', 'projects'),
    list('Case studies', 'case-studies'),
    list('Experience', 'experience'),
    list('Education', 'education'),
    list('Languages', 'languages'),
    list('Testimonials', 'testimonials'),
    list('Open source', 'open-source'),
]

onMounted(() => {
    navigation.value?.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest', inline: 'center' })
})
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Portfolio"
            description="Edit what visitors see. Public pages can take up to 5 minutes to show your changes."
            class-names="mb-0"
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-10">
            <nav ref="navigation" aria-label="Portfolio" class="flex gap-1 overflow-x-auto lg:w-48 lg:shrink-0 lg:flex-col">
                <Button
                    v-for="section in sections"
                    :key="section.href"
                    variant="ghost"
                    size="sm"
                    :class="['justify-start', { 'bg-accent text-accent-foreground': isNavItemActive(section) }]"
                    as-child
                >
                    <Link :href="section.href" :aria-current="isNavItemActive(section) ? 'page' : undefined">{{ section.title }}</Link>
                </Button>
            </nav>

            <section class="min-w-0 flex-1 lg:max-w-3xl">
                <slot />
            </section>
        </div>
    </div>
</template>
