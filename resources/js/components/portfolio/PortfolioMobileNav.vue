<script setup lang="ts">
import {
    BriefcaseBusiness,
    Code2,
    FolderKanban,
    House,
    Send,
    User,
} from '@lucide/vue';
import { nextTick, watch } from 'vue';
import { portfolioSections, useScrollSpy } from '@/composables/useScrollSpy';

const { activeId, scrollTo } = useScrollSpy();

const sectionIcons = {
    hero: House,
    about: User,
    skills: Code2,
    projects: FolderKanban,
    experience: BriefcaseBusiness,
    connect: Send,
} as const;

watch(activeId, async () => {
    await nextTick();

    document.getElementById(`mobile-nav-${activeId.value}`)?.scrollIntoView({
        inline: 'center',
        behavior: 'smooth',
        block: 'nearest',
    });
});
</script>

<template>
    <nav aria-label="Sections" class="fixed inset-x-4 bottom-4 z-40 lg:hidden">
        <div
            class="mask-fade mx-auto flex max-w-md items-center gap-1 overflow-x-auto rounded-full border border-border/40 bg-background/50 p-1.5 shadow-lg backdrop-blur-xl"
            style="scrollbar-width: none"
        >
            <button
                v-for="section in portfolioSections"
                :id="`mobile-nav-${section.id}`"
                :key="section.id"
                type="button"
                class="flex shrink-0 snap-center items-center gap-1.5 rounded-full px-4 py-2 text-xs font-medium tracking-wide whitespace-nowrap uppercase transition-colors"
                :class="
                    activeId === section.id
                        ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="scrollTo(section.id)"
            >
                <component
                    :is="sectionIcons[section.id as keyof typeof sectionIcons]"
                    class="size-3.5"
                />
                {{ section.label }}
            </button>
        </div>
    </nav>
</template>
