<script setup lang="ts">
import MediaSlotCard from '@/components/app/portfolio/MediaSlotCard.vue'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import type { PortfolioMediaSlot } from '@/types/portfolio'

defineProps<{ slots: PortfolioMediaSlot[] }>()
</script>

<template>
    <PortfolioLayout>
        <div class="grid gap-6">
            <MediaSlotCard v-for="slot in slots" :key="slot.key" :slot="slot" />
        </div>
    </PortfolioLayout>
</template>
