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
import { computed, h, ref } from 'vue';
import PortfolioSection from '@/components/portfolio/PortfolioSection.vue';
import type { Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const copied = ref<'email' | 'phone' | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | undefined;

const formattedPhone = computed(() =>
    props.portfolio.phone.replace(/^(\+\d{3})(\d{4})(\d{6})$/, '$1 $2 $3'),
);

const LinkedInIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
        h('path', {
            d: 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z',
        }),
    ]);

const iconMap = {
    linkedin: LinkedInIcon,
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

const primaryLinks = computed(() =>
    links.value.filter((link) => link.isPrimary),
);

const secondaryLinks = computed(() =>
    links.value.filter((link) => !link.isPrimary),
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
        title="Get in Touch"
        description="For roles, consulting, or project enquiries, email or LinkedIn is the best way to reach me. I usually reply within one business day."
    >
        <div class="flex flex-wrap gap-3">
            <a
                v-for="{ platform, url, label, Icon } in primaryLinks"
                :key="platform"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2.5 rounded-full border border-border bg-background/50 px-5 py-2.5 text-sm font-medium backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:text-emerald-800 dark:hover:text-emerald-400"
            >
                <component
                    :is="Icon"
                    class="size-4 text-emerald-600 dark:text-emerald-400"
                    aria-hidden="true"
                />
                {{ label ?? platform }}
                <ArrowUpRight
                    class="size-3.5 text-muted-foreground"
                    aria-hidden="true"
                />
                <span class="sr-only">(opens in a new tab)</span>
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div
                class="flex items-start justify-between gap-4 rounded-xl border border-border bg-background/50 p-5 backdrop-blur-sm"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Mail
                        class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
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
                                    aria-hidden="true"
                                />
                                <Copy
                                    v-else
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{ copied === 'email' ? 'Copied!' : 'Copy' }}
                            </button>
                            <a
                                :href="`mailto:${portfolio.email}`"
                                class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                Draft an Email
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
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
                        aria-hidden="true"
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
                                    aria-hidden="true"
                                />
                                <Copy
                                    v-else
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{ copied === 'phone' ? 'Copied!' : 'Copy' }}
                            </button>
                            <a
                                :href="`tel:${portfolio.phone}`"
                                class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                Call
                                <ArrowUpRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="secondaryLinks.length"
            class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground"
        >
            <span>Also on:</span>
            <a
                v-for="{ platform, url, label } in secondaryLinks"
                :key="platform"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="underline-offset-4 transition-colors hover:text-foreground hover:underline"
            >
                {{ label ?? platform }}
                <span class="sr-only">(opens in a new tab)</span>
            </a>
        </div>
    </PortfolioSection>
</template>
