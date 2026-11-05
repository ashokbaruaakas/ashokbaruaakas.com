<script setup lang="ts">
import AppBrand from '@/components/AppBrand.vue'
import AppearanceToggle from '@/components/AppearanceToggle.vue'
import { home } from '@/routes'
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <header class="flex h-14 items-center justify-between px-4 sm:px-6">
        <Link :href="home.url()" :aria-label="`${$page.props.name} — home`" class="rounded-md">
            <AppBrand />
        </Link>
        <AppearanceToggle />
    </header>
</template>
