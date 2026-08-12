<script setup lang="ts">
import { Heart } from '@lucide/vue';
import type { Portfolio } from '@/types/portfolio';

defineProps<{ portfolio: Portfolio }>();
</script>

<template>
    <footer class="border-t border-border/60 px-6 py-10">
        <div
            class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 text-sm text-muted-foreground sm:flex-row"
        >
            <p>
                &copy; {{ new Date().getFullYear() }} {{ portfolio.name }} ·
                {{ portfolio.location }}
            </p>
            <p class="inline-flex items-center gap-1.5">
                Built with
                <Heart class="size-3.5 fill-emerald-500 text-emerald-500" />
                using Laravel &amp; Vue
            </p>
        </div>
    </footer>
</template>
