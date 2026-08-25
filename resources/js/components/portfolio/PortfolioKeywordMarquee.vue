<script setup lang="ts">
const codeWords = [
    'const',
    'await',
    'artisan',
    'eloquent',
    'migrate',
    'dispatch',
    'resolve',
    'schedule',
    'boot',
    'queue',
    'listen',
    'compile',
];

const codeLine = [...codeWords, ...codeWords].join(' · ') + ' ·';
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none fixed inset-x-0 bottom-0 z-[-5] overflow-hidden select-none"
    >
        <p
            class="font-mono text-[100px] leading-none tracking-tight whitespace-nowrap opacity-[0.04] motion-safe:animate-[scrollCode_50s_linear_infinite] lg:text-[160px] dark:opacity-[0.03]"
        >
            {{ codeLine }}
        </p>
    </div>
</template>
