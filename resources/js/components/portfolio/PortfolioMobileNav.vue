<script setup lang="ts">
import { ChevronUp } from '@lucide/vue';
import { computed, ref } from 'vue';
import { portfolioSections, useScrollSpy } from '@/composables/useScrollSpy';

const { activeId, scrollTo } = useScrollSpy();
const open = ref(false);

const activeSection = computed(
    () =>
        portfolioSections.find((section) => section.id === activeId.value) ??
        portfolioSections[0],
);

function select(id: string) {
    scrollTo(id);
    open.value = false;
}
</script>

<template>
    <div class="fixed inset-x-4 bottom-4 z-40 lg:hidden">
        <div
            class="mx-auto flex max-w-md items-center justify-between gap-3 rounded-full border border-border/70 bg-background/90 p-2 pl-4 shadow-lg backdrop-blur-md"
        >
            <button
                type="button"
                class="flex min-w-0 items-center gap-2 text-sm font-medium"
                :aria-expanded="open"
                aria-haspopup="menu"
                @click="open = !open"
            >
                <span
                    class="size-2 shrink-0 rounded-full bg-emerald-500"
                    :class="{ 'scale-125': !open }"
                />
                <span class="truncate">{{ activeSection?.label }}</span>
                <ChevronUp
                    class="size-4 shrink-0 text-muted-foreground transition-transform"
                    :class="{ 'rotate-180': open }"
                />
            </button>

            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150"
                leave-to-class="opacity-0"
            >
                <button
                    v-if="open"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
                    @click="select(activeSection?.id ?? 'hero')"
                >
                    Jump
                </button>
            </Transition>
        </div>

        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="mx-auto mt-2 max-w-md overflow-hidden rounded-2xl border border-border/70 bg-background/95 shadow-lg backdrop-blur-md"
                role="menu"
            >
                <div class="grid gap-1 p-2">
                    <button
                        v-for="section in portfolioSections"
                        :key="section.id"
                        type="button"
                        role="menuitem"
                        class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm transition-colors"
                        :class="
                            activeId === section.id
                                ? 'bg-emerald-600/10 font-medium text-emerald-700 dark:text-emerald-400'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                        "
                        @click="select(section.id)"
                    >
                        {{ section.label }}
                        <span
                            v-if="activeId === section.id"
                            class="size-1.5 rounded-full bg-emerald-500"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
