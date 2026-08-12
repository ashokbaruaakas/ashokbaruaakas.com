<script setup lang="ts">
import { useIntersectionObserver } from '@vueuse/core';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        id: string;
        title: string;
        eyebrow?: string;
        description?: string;
    }>(),
    { eyebrow: undefined, description: undefined },
);

const element = ref<HTMLElement | null>(null);
const visible = ref(false);

useIntersectionObserver(
    element,
    ([entry]) => {
        if (entry.isIntersecting) {
            visible.value = true;
        }
    },
    { threshold: 0.12, rootMargin: '0px 0px -10% 0px' },
);
</script>

<template>
    <section :id="props.id" class="scroll-mt-24">
        <div
            ref="element"
            class="transition-all duration-700 motion-reduce:transition-none"
            :class="
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-6 opacity-0 motion-reduce:translate-y-0 motion-reduce:opacity-100'
            "
        >
            <p
                v-if="props.eyebrow"
                class="mb-3 text-sm font-semibold tracking-widest text-emerald-600 uppercase dark:text-emerald-400"
            >
                {{ props.eyebrow }}
            </p>
            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                {{ props.title }}
            </h2>
            <p
                v-if="props.description"
                class="mt-4 max-w-2xl text-muted-foreground"
            >
                {{ props.description }}
            </p>
            <div class="mt-10">
                <slot />
            </div>
        </div>
    </section>
</template>
