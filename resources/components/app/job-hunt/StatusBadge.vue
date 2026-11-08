<script setup lang="ts">
import { statusClass } from '@/components/app/job-hunt/jobHunt'
import { cn } from '@/lib/utils'

defineProps<{
    status: string
    label: string
    muted?: boolean
}>()
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                muted ? 'text-muted-foreground ring-1 ring-border ring-inset hover:text-foreground' : statusClass(status),
            )
        "
        >{{ label }}</span
    >
</template>
