<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleAppearance() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6"
        >
            <a href="#hero" class="flex items-center gap-2.5">
                <img
                    src="https://raw.githubusercontent.com/ashokbaruaakas/ashokbaruaakas.github.io/refs/heads/master/public/favicon.ico"
                    alt=""
                    class="size-6"
                />
                <span class="text-sm font-semibold tracking-tight">
                    Ashok Barua Akas
                </span>
            </a>

            <div class="flex items-center gap-2">
                <span
                    class="hidden items-center gap-1.5 text-xs text-muted-foreground sm:flex"
                >
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                        />
                    </span>
                    Available for work
                </span>

                <button
                    type="button"
                    class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    :aria-label="
                        resolvedAppearance === 'dark'
                            ? 'Switch to light mode'
                            : 'Switch to dark mode'
                    "
                    @click="toggleAppearance"
                >
                    <Sun
                        v-if="resolvedAppearance === 'dark'"
                        class="size-4.5"
                    />
                    <Moon v-else class="size-4.5" />
                </button>
            </div>
        </div>
    </header>
</template>
