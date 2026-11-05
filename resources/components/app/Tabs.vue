<script setup lang="ts">
import { isNavItemActive } from '@/lib/helpers'
import { cn } from '@/lib/utils'
import { Link } from '@inertiajs/vue3'
import { useAppNavigation } from './navigation'

const { mainNavItems } = useAppNavigation()
</script>

<template>
    <nav aria-label="Main" class="flex [scrollbar-width:none] gap-6 overflow-x-auto border-b px-4 sm:px-6 [&::-webkit-scrollbar]:hidden">
        <Link
            v-for="item in mainNavItems"
            :key="item.href"
            :href="item.href"
            :aria-current="isNavItemActive(item) ? 'page' : undefined"
            prefetch
            :class="
                cn(
                    '-mb-px flex min-h-12 shrink-0 items-center border-b-2 text-sm font-medium transition-colors',
                    isNavItemActive(item)
                        ? 'border-emerald-700 text-foreground dark:border-emerald-400'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            {{ item.title }}
        </Link>
    </nav>
</template>
