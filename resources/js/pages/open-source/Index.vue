<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Check,
    Code2,
    ExternalLink,
    GitPullRequest,
    Package,
    Rocket,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PortfolioBackLink from '@/components/portfolio/PortfolioBackLink.vue';
import type { OpenSourceContribution, Portfolio } from '@/types/portfolio';

const props = defineProps<{ portfolio: Portfolio }>();

const selectedTag = ref('All');

const tags = computed(() => [
    'All',
    ...new Set(
        props.portfolio.openSourceContributions.flatMap(
            (contribution) => contribution.tags,
        ),
    ),
]);

const filteredContributions = computed(() =>
    selectedTag.value === 'All'
        ? props.portfolio.openSourceContributions
        : props.portfolio.openSourceContributions.filter((contribution) =>
              contribution.tags.includes(selectedTag.value),
          ),
);

function contributionIcon(contribution: OpenSourceContribution) {
    if (contribution.type === 'Pull Request') {
        return GitPullRequest;
    }

    if (contribution.metric) {
        return Package;
    }

    return contribution.type === 'Organization' ? Rocket : Code2;
}
</script>

<template>
    <Head title="Open Source" />

    <div class="px-6 pt-24 pb-16 lg:px-12">
        <div class="mx-auto max-w-3xl">
            <div class="mb-16">
                <PortfolioBackLink />

                <p
                    class="mb-3 text-sm font-semibold tracking-widest text-emerald-600 uppercase dark:text-emerald-400"
                >
                    Community work
                </p>
                <h1
                    class="font-display text-5xl font-bold tracking-tight sm:text-6xl"
                >
                    Open Source
                </h1>
                <p
                    class="mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground"
                >
                    Contributions, projects, and tools I have built for the
                    developer community.
                </p>
            </div>

            <div
                class="mb-12 flex flex-wrap gap-2"
                aria-label="Filter contributions by tag"
            >
                <button
                    v-for="tag in tags"
                    :key="tag"
                    type="button"
                    class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        selectedTag === tag
                            ? 'border-emerald-500 bg-emerald-500 text-white'
                            : 'border-border bg-background/50 text-muted-foreground hover:border-emerald-500/50 hover:text-foreground'
                    "
                    :aria-pressed="selectedTag === tag"
                    @click="selectedTag = tag"
                >
                    {{ tag }}
                </button>
            </div>

            <div class="relative">
                <div
                    aria-hidden="true"
                    class="absolute top-2 bottom-2 left-3 w-px bg-gradient-to-b from-emerald-500/60 via-border to-transparent sm:left-4"
                />

                <div class="flex flex-col gap-10">
                    <article
                        v-for="contribution in filteredContributions"
                        :key="contribution.title"
                        class="relative pl-10 sm:pl-12"
                    >
                        <div
                            class="absolute top-1.5 left-0 flex size-6 items-center justify-center rounded-full border border-emerald-500/50 bg-background text-emerald-500 sm:size-8"
                        >
                            <component
                                :is="contributionIcon(contribution)"
                                class="size-3.5 sm:size-4"
                            />
                        </div>

                        <div
                            class="rounded-xl border border-border/70 bg-background/55 p-5 shadow-sm backdrop-blur-sm transition-colors hover:border-emerald-500/30 sm:p-6"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="font-mono text-xs tracking-wide text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ contribution.date }}
                                    </p>
                                    <h2
                                        class="mt-2 text-lg font-semibold tracking-tight"
                                    >
                                        {{ contribution.title }}
                                    </h2>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ contribution.organization }} ·
                                        {{ contribution.type }}
                                    </p>
                                </div>
                                <span
                                    v-if="contribution.metric"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                                >
                                    <Check class="size-3.5" />
                                    {{ contribution.metric }}
                                </span>
                            </div>

                            <p
                                class="mt-4 text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ contribution.description }}
                            </p>

                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                <span
                                    v-for="tag in contribution.tags"
                                    :key="tag"
                                    class="rounded-md bg-muted/70 px-2 py-1 text-[11px] font-medium text-muted-foreground"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <div
                                class="mt-5 flex flex-wrap gap-4 text-sm font-medium"
                            >
                                <a
                                    :href="contribution.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-emerald-700 transition-colors hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    View on GitHub
                                    <ArrowUpRight class="size-4" />
                                </a>
                                <a
                                    v-if="contribution.secondaryUrl"
                                    :href="contribution.secondaryUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    More details
                                    <ExternalLink class="size-3.5" />
                                </a>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-if="filteredContributions.length === 0"
                    class="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground"
                >
                    No contributions match this tag yet.
                </div>
            </div>

            <div class="mt-16 border-t border-border/60 pt-8">
                <p class="text-sm text-muted-foreground">
                    I enjoy working on Laravel, Inertia, Vue, developer tooling,
                    AI workflows, and infrastructure projects.
                </p>
                <a
                    :href="`https://github.com/${portfolio.githubUsername}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 transition-colors hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    Follow more work on GitHub
                    <ArrowUpRight class="size-4" />
                </a>
            </div>
        </div>
    </div>
</template>
