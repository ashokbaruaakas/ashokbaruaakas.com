<script setup lang="ts">
import {
    ArrowUpRight,
    AtSign,
    Check,
    Code,
    Copy,
    Mail,
    MessageCircle,
    MessageSquare,
    Phone,
    Send,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const copied = ref<'email' | 'phone' | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | undefined;

const formattedPhone = computed(() =>
    props.portfolio.phone.replace(/^(\+\d{3})(\d{4})(\d{6})$/, '$1 $2 $3'),
);

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

function copyText(kind: 'email' | 'phone') {
    const value =
        kind === 'email' ? props.portfolio.email : props.portfolio.phone;

    navigator.clipboard.writeText(value);
    copied.value = kind;

    clearTimeout(copyTimer);
    copyTimer = setTimeout(() => {
        copied.value = null;
    }, 2000);
}
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

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div
                class="flex items-start justify-between gap-4 rounded-xl border border-border bg-background/50 p-5 backdrop-blur-sm"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Mail
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Email
                        </p>
                        <p
                            class="mt-1 truncate text-sm font-medium"
                            :title="portfolio.email"
                        >
                            {{ portfolio.email }}
                        </p>
                        <div class="mt-3 flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1 text-xs font-medium transition-colors hover:bg-accent"
                                @click="copyText('email')"
                            >
                                <Check
                                    v-if="copied === 'email'"
                                    class="size-3.5 text-emerald-500"
                                />
                                <Copy v-else class="size-3.5" />
                                {{ copied === 'email' ? 'Copied!' : 'Copy' }}
                            </button>
                            <a
                                :href="`mailto:${portfolio.email}`"
                                class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                Draft an Email
                                <ArrowUpRight class="size-3.5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-start justify-between gap-4 rounded-xl border border-border bg-background/50 p-5 backdrop-blur-sm"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Phone
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Phone
                        </p>
                        <p
                            class="mt-1 truncate text-sm font-medium"
                            :title="formattedPhone"
                        >
                            {{ formattedPhone }}
                        </p>
                        <div class="mt-3 flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1 text-xs font-medium transition-colors hover:bg-accent"
                                @click="copyText('phone')"
                            >
                                <Check
                                    v-if="copied === 'phone'"
                                    class="size-3.5 text-emerald-500"
                                />
                                <Copy v-else class="size-3.5" />
                                {{ copied === 'phone' ? 'Copied!' : 'Copy' }}
                            </button>
                            <a
                                :href="`tel:${portfolio.phone}`"
                                class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                Call
                                <ArrowUpRight class="size-3.5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PortfolioSection>
</template>
