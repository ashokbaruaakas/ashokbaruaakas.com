<script setup lang="ts">
import { useInitials } from '@/composables/useInitials'
import { cn } from '@/lib/utils'
import { usePage } from '@inertiajs/vue3'
import { computed, type HTMLAttributes } from 'vue'

interface Props {
    class?: HTMLAttributes['class']
    iconOnly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    iconOnly: false,
})

const page = usePage()
const { getInitials } = useInitials()

const name = computed(() => page.props.name as string)
</script>

<template>
    <span :class="cn('inline-flex items-center gap-2.5', props.class)">
        <img v-if="page.props.media.avatar" :src="page.props.media.avatar" alt="" width="32" height="32" class="size-8 shrink-0 rounded-full" />
        <span
            v-else
            aria-hidden="true"
            class="flex size-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-xs font-semibold text-emerald-700 dark:text-emerald-400"
        >
            {{ getInitials(name) }}
        </span>
        <span
            :class="
                cn(
                    'bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-700 bg-clip-text text-sm font-semibold tracking-tight whitespace-nowrap text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500',
                    iconOnly && 'sr-only',
                )
            "
        >
            {{ name }}
        </span>
    </span>
</template>
