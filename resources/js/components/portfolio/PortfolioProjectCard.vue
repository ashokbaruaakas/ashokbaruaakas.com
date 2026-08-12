<script setup lang="ts">
import { ArrowUpRight, Star } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { Project } from '@/types/portfolio';

defineProps<{ project: Project }>();

const repoUrl = ({ owner, repo }: Project) =>
    `https://github.com/${owner}/${repo}`;
</script>

<template>
    <a
        :href="project.demoUrl ?? repoUrl(project)"
        target="_blank"
        rel="noopener noreferrer"
        class="group flex flex-col rounded-xl border border-border bg-background/60 p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5"
    >
        <div class="flex items-start justify-between gap-4">
            <h3
                class="font-medium group-hover:text-emerald-700 dark:group-hover:text-emerald-400"
            >
                {{ project.name }}
            </h3>
            <ArrowUpRight
                class="size-4 shrink-0 text-muted-foreground transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-emerald-600"
            />
        </div>

        <p class="mt-3 flex-1 text-sm text-muted-foreground">
            {{ project.description }}
        </p>

        <div class="mt-5 flex flex-wrap gap-1.5">
            <Badge
                v-for="tech in project.technologies"
                :key="tech"
                variant="secondary"
            >
                {{ tech }}
            </Badge>
        </div>

        <div
            class="mt-5 flex items-center justify-between border-t border-border/60 pt-4 text-xs text-muted-foreground"
        >
            <span class="inline-flex items-center gap-1.5">
                <span
                    class="size-2 rounded-full bg-emerald-500"
                    :title="project.language"
                />
                {{ project.language }}
            </span>
            <span class="inline-flex items-center gap-1">
                <Star class="size-3.5" />
                {{ project.stars }}
            </span>
        </div>
    </a>
</template>
