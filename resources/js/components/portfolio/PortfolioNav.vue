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
                    src="https://avatars.githubusercontent.com/u/55571706?size=64"
                    alt=""
                    class="size-6 rounded-full"
                />
                <span
                    class="font-mono text-xs font-semibold tracking-[0.3em] uppercase"
                >
                    ASHOKBARUAAKAS
                </span>
            </a>

            <div class="flex items-center gap-2">
                <span
                    class="hidden items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium text-emerald-700 sm:inline-flex dark:text-emerald-400"
                >
                    <span class="relative flex size-1.5">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex size-1.5 rounded-full bg-emerald-500"
                        />
                    </span>
                    Available for work
                </span>

                <a
                    href="https://github.com/ashokbaruaakas"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="GitHub profile"
                    class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <svg
                        class="size-4"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"
                        />
                    </svg>
                </a>

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
