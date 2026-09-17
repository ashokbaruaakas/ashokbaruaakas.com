<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { home } from '@/routes';
</script>

<template>
    <Link
        :href="home()"
        class="mb-8 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground print:hidden"
    >
        <ArrowLeft class="size-4" />
        Back to Portfolio
    </Link>
</template>
