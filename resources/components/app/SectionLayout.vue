<script setup lang="ts">
import { isNavItemActive } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import type { NavGroup } from '@/types'
import { Link } from '@inertiajs/vue3'
import { computed, onMounted, useTemplateRef } from 'vue'

const props = defineProps<{
    label: string
    groups: NavGroup[]
    description?: string
}>()

const navigation = useTemplateRef<HTMLElement>('navigation')

const title = computed(() => props.groups.flatMap((group) => group.items).find((item) => isNavItemActive(item))?.title ?? props.label)

onMounted(() => {
    navigation.value?.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest', inline: 'center' })
})
</script>

<template>
    <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-16">
        <nav
            ref="navigation"
            :aria-label="label"
            class="flex [scrollbar-width:none] gap-1 overflow-x-auto lg:sticky lg:top-20 lg:order-last lg:w-56 lg:shrink-0 lg:flex-col lg:gap-4 [&::-webkit-scrollbar]:hidden"
        >
            <div v-for="(group, index) in groups" :key="group.label ?? index" class="flex shrink-0 gap-1 lg:flex-col">
                <p v-if="group.label" class="hidden px-2.5 pb-1 text-xs font-medium text-muted-foreground lg:block">{{ group.label }}</p>
                <Link
                    v-for="item in group.items"
                    :key="item.href"
                    :href="item.href"
                    :aria-current="isNavItemActive(item) ? 'page' : undefined"
                    :class="
                        cn(
                            'flex min-h-9 shrink-0 items-center rounded-md px-2.5 text-sm transition-colors',
                            isNavItemActive(item)
                                ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </div>
        </nav>

        <section class="flex min-w-0 flex-1 flex-col gap-6">
            <div class="flex flex-col gap-1">
                <h1 class="font-display text-xl font-bold tracking-tight">{{ title }}</h1>
                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
            </div>

            <slot />
        </section>
    </div>
</template>
