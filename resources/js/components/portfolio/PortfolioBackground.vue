<script setup lang="ts"></script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none fixed inset-0 -z-10"
    >
        <div
            class="absolute -top-40 right-[-10%] size-[600px] rounded-full bg-[radial-gradient(circle,rgba(16,185,129,0.18),transparent_70%)] blur-[100px]"
        />
        <div
            class="absolute bottom-[-20%] left-[-10%] size-[600px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.15),transparent_70%)] blur-[100px]"
        />
        <div
            class="absolute inset-0"
            :style="{
                backgroundImage:
                    'radial-gradient(circle, var(--color-border) 1px, transparent 1px)',
                backgroundSize: '32px 32px',
            }"
        />
        <div class="grain-overlay absolute inset-0" />
    </div>
</template>
