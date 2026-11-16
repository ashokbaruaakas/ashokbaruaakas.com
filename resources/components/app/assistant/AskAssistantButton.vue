<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Sparkles } from '@lucide/vue'

defineProps<{ label: string }>()

const emit = defineEmits<{ ask: [] }>()
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        size="icon-xs"
        class="text-muted-foreground"
        :aria-label="`Ask the Assistant About ${label}`"
        :title="`Ask the Assistant About ${label}`"
        @click="emit('ask')"
    >
        <Sparkles />
    </Button>
</template>
