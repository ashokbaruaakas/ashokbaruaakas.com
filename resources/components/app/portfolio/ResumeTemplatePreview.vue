<script setup lang="ts">
import { useElementSize } from '@vueuse/core'
import { computed, ref } from 'vue'

defineProps<{
    src: string
    title: string
}>()

const FRAME_WIDTH = 820
const FRAME_HEIGHT = 1160

const container = ref<HTMLElement | null>(null)
const { width } = useElementSize(container)

const scale = computed(() => (width.value > 0 ? width.value / FRAME_WIDTH : 0))
</script>

<template>
    <div ref="container" class="relative aspect-[210/297] w-full overflow-hidden rounded-sm border bg-neutral-200">
        <iframe
            :src="src"
            :title="title"
            loading="lazy"
            tabindex="-1"
            aria-hidden="true"
            class="pointer-events-none absolute top-0 left-0 origin-top-left border-0"
            :style="{ width: `${FRAME_WIDTH}px`, height: `${FRAME_HEIGHT}px`, transform: `scale(${scale})` }"
        />
    </div>
</template>
