<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { dashboard } from '@/routes'
</script>

<template>
    <Head title="Welcome">
        <meta head-key="robots" name="robots" content="noindex" />
    </Head>

    <main class="flex min-h-screen flex-col items-center justify-center gap-4 bg-background px-6 text-center text-foreground">
        <h1 class="text-3xl font-semibold tracking-tight">Welcome</h1>
        <p class="max-w-md text-muted-foreground">
            This portfolio doesn't have any information yet. Add it from the dashboard and it will appear here.
        </p>
        <Link
            :href="dashboard()"
            class="inline-flex items-center rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
        >
            Go to dashboard
        </Link>
    </main>
</template>
