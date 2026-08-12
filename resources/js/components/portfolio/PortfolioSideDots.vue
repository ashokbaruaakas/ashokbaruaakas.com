<script setup lang="ts">
import { computed } from 'vue';
import { portfolioSections, useScrollSpy } from '@/composables/useScrollSpy';

const { activeId, scrollTo } = useScrollSpy();

const activeIndex = computed(() =>
    portfolioSections.findIndex((section) => section.id === activeId.value),
);

const progress = computed(
    () => ((activeIndex.value + 1) / portfolioSections.length) * 100,
);
</script>

<template>
    <nav
        aria-label="Sections"
        class="fixed top-1/2 right-6 z-40 hidden -translate-y-1/2 lg:flex"
    >
        <div class="relative flex items-center gap-3">
            <div class="absolute inset-y-0 right-[7px] w-px bg-border">
                <div
                    class="w-px bg-emerald-500 transition-[height] duration-300"
                    :style="{ height: `${progress}%` }"
                />
            </div>

            <div class="flex flex-col justify-center gap-5 py-2">
                <button
                    v-for="section in portfolioSections"
                    :key="section.id"
                    type="button"
                    :aria-label="`Scroll to ${section.label}`"
                    :aria-current="activeId === section.id ? 'true' : undefined"
                    class="group flex cursor-pointer items-center justify-end gap-3"
                    @click="scrollTo(section.id)"
                >
                    <span
                        class="pointer-events-none w-16 text-right text-xs font-medium text-muted-foreground opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                        :class="
                            activeId === section.id
                                ? 'text-emerald-700 opacity-100 dark:text-emerald-400'
                                : ''
                        "
                    >
                        {{ section.label }}
                    </span>
                    <span
                        class="relative z-10 block h-2 w-2 rounded-full transition-all duration-300"
                        :class="
                            activeId === section.id
                                ? 'scale-125 bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.6)]'
                                : 'bg-border group-hover:bg-muted-foreground'
                        "
                    />
                </button>
            </div>
        </div>
    </nav>
</template>
