<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { getScrollBehavior } from '@/composables/useScrollSpy';
import { home } from '@/routes';

const contactUrl = `${home.url()}#connect`;

function goToContact(event: MouseEvent) {
    if (
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return;
    }

    event.preventDefault();

    const contactSection = document.getElementById('connect');

    if (contactSection) {
        contactSection.scrollIntoView({ behavior: getScrollBehavior() });

        return;
    }

    router.visit(contactUrl);
}
</script>

<template>
    <a
        :href="contactUrl"
        aria-label="Open to Remote or Relocation — get in touch"
        class="items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium whitespace-nowrap text-emerald-700 transition-colors hover:border-emerald-500/50 hover:bg-emerald-500/20 focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:outline-none dark:text-emerald-400"
        @click="goToContact"
    >
        <span class="relative flex size-1.5">
            <span
                class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping"
                aria-hidden="true"
            />
            <span
                class="relative inline-flex size-1.5 rounded-full bg-emerald-500"
            />
        </span>
        Open to Remote or Relocation
    </a>
</template>
