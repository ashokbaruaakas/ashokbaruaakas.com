<script setup lang="ts">
import { portfolioSections, useScrollSpy } from '@/composables/useScrollSpy';

const { activeId, scrollTo } = useScrollSpy();
</script>

<template>
    <nav
        aria-label="Sections"
        class="fixed top-1/2 right-6 z-40 hidden -translate-y-1/2 flex-col items-center gap-3 lg:flex"
    >
        <button
            v-for="section in portfolioSections"
            :key="section.id"
            type="button"
            :aria-label="`Scroll to ${section.label}`"
            :aria-current="activeId === section.id ? 'true' : undefined"
            class="group flex items-center justify-end gap-2.5"
            @click="scrollTo(section.id)"
        >
            <span
                class="pointer-events-none translate-x-1 text-xs font-medium text-muted-foreground opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100"
            >
                {{ section.label }}
            </span>
            <span
                class="block h-2 w-2 rounded-full transition-all duration-300"
                :class="
                    activeId === section.id
                        ? 'scale-125 bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.6)]'
                        : 'bg-border hover:bg-muted-foreground'
                "
            />
        </button>
    </nav>
</template>
