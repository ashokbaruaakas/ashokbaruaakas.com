<script setup lang="ts">
import {
    ArrowUpRight,
    AtSign,
    Code,
    MessageCircle,
    MessageSquare,
    Send,
} from '@lucide/vue';
import { computed } from 'vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const iconMap = {
    github: Code,
    twitter: AtSign,
    send: Send,
    'message-circle': MessageCircle,
    'message-square': MessageSquare,
} as const;

const links = computed(() =>
    props.portfolio.socialLinks.map((link) => ({
        ...link,
        Icon: iconMap[link.icon as keyof typeof iconMap] ?? ArrowUpRight,
    })),
);
</script>

<template>
    <PortfolioSection
        id="connect"
        eyebrow="Connect"
        title="Let's Work Together"
        description="Whether you have a project in mind, a question, or just want to say hi — reach out on any of these."
    >
        <div class="flex flex-wrap gap-3">
            <a
                v-for="{ platform, url, label, Icon } in links"
                :key="platform"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2.5 rounded-full border border-border bg-background/50 px-5 py-2.5 text-sm font-medium backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:text-emerald-700 dark:hover:text-emerald-400"
            >
                <component
                    :is="Icon"
                    class="size-4 text-emerald-600 dark:text-emerald-400"
                />
                {{ label ?? platform }}
                <ArrowUpRight class="size-3.5 text-muted-foreground" />
            </a>
        </div>
    </PortfolioSection>
</template>
