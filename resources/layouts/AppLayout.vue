<script setup lang="ts">
import CommandPalette from '@/components/app/CommandPalette.vue'
import Header from '@/components/app/Header.vue'
import ResumePdfStatus from '@/components/app/ResumePdfStatus.vue'
import Tabs from '@/components/app/Tabs.vue'
import { usePageMeta } from '@/composables/usePageMeta'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const meta = usePageMeta()

const isCommandPaletteOpen = ref(false)
</script>

<template>
    <Head :title="meta.title" />

    <div data-surface="app" class="flex min-h-svh flex-col bg-sidebar px-2 pb-2 text-foreground antialiased">
        <Header @search="isCommandPaletteOpen = true" />

        <div class="flex min-w-0 flex-1 flex-col rounded-xl border bg-background">
            <Tabs />
            <ResumePdfStatus />

            <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
                <slot />
            </main>
        </div>

        <CommandPalette v-model:open="isCommandPaletteOpen" />
    </div>
</template>
