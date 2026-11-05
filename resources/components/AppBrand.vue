<script setup lang="ts">
import { useInitials } from '@/composables/useInitials'
import { cn } from '@/lib/utils'
import { usePage } from '@inertiajs/vue3'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()

const page = usePage()
const { getInitials } = useInitials()

const name = computed(() => page.props.name as string)
const headline = computed(() => page.props.headline as string | null)
</script>

<template>
    <span :class="cn('inline-flex min-w-0 items-center gap-2.5', props.class)">
        <img v-if="page.props.media.avatar" :src="page.props.media.avatar" alt="" width="32" height="32" class="size-8 shrink-0 rounded-full" />
        <span
            v-else
            aria-hidden="true"
            class="flex size-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-xs font-semibold text-emerald-700 dark:text-emerald-400"
        >
            {{ getInitials(name) }}
        </span>
        <span class="flex min-w-0 flex-col leading-tight">
            <span
                class="truncate bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-700 bg-clip-text text-sm font-semibold tracking-tight text-transparent dark:from-emerald-400 dark:via-teal-300 dark:to-emerald-500"
            >
                {{ name }}
            </span>
            <span v-if="headline" class="truncate text-xs text-muted-foreground">{{ headline }}</span>
        </span>
    </span>
</template>
