<script setup lang="ts">
import { ArrowUpRight, Star } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { Project } from '@/types/portfolio';

withDefaults(defineProps<{ project: Project; featured?: boolean }>(), {
    featured: false,
});

const projectUrl = ({ demoUrl, owner, repo }: Project) =>
    demoUrl ?? (owner && repo ? `https://github.com/${owner}/${repo}` : null);
</script>

<template>
    <component
        :is="
            project.secondaryUrl
                ? 'article'
                : projectUrl(project)
                  ? 'a'
                  : 'article'
        "
        :href="
            project.secondaryUrl
                ? undefined
                : (projectUrl(project) ?? undefined)
        "
        :target="
            project.secondaryUrl
                ? undefined
                : projectUrl(project)
                  ? '_blank'
                  : undefined
        "
        :rel="
            project.secondaryUrl
                ? undefined
                : projectUrl(project)
                  ? 'noopener noreferrer'
                  : undefined
        "
        class="group flex flex-col rounded-xl border border-border bg-background/60 backdrop-blur-sm transition-all duration-300"
        :class="[
            featured ? 'p-7 md:min-h-80' : 'p-6',
            projectUrl(project)
                ? 'hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-lg hover:shadow-emerald-500/5'
                : '',
        ]"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        v-if="project.secondaryUrl"
                        :href="projectUrl(project) ?? undefined"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium group-hover:text-emerald-700 dark:group-hover:text-emerald-400"
                    >
                        {{ project.name }}
                    </a>
                    <h3
                        v-else
                        class="font-medium group-hover:text-emerald-700 dark:group-hover:text-emerald-400"
                    >
                        {{ project.name }}
                    </h3>
                    <span
                        v-if="project.metric"
                        class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                    >
                        {{ project.metric }}
                    </span>
                </div>
                <p
                    v-if="project.period"
                    class="mt-1 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ project.period }}
                </p>
            </div>
            <ArrowUpRight
                v-if="projectUrl(project)"
                class="size-4 shrink-0 text-muted-foreground transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-emerald-600"
            />
        </div>

        <p class="mt-3 text-sm text-muted-foreground">
            {{ project.description }}
        </p>

        <p v-if="project.role" class="mt-4 text-sm leading-relaxed font-medium">
            {{ project.role }}
        </p>

        <ul v-if="project.highlights.length" class="mt-4 space-y-1.5">
            <li
                v-for="highlight in project.highlights"
                :key="highlight"
                class="relative pl-4 text-sm text-muted-foreground"
            >
                <span
                    class="absolute top-2 left-0 size-1.5 rounded-full bg-emerald-500"
                    aria-hidden="true"
                />
                {{ highlight }}
            </li>
        </ul>

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
            <div class="flex items-center gap-4">
                <span
                    v-if="project.linkLabel"
                    class="inline-flex items-center gap-1 font-medium text-emerald-700 dark:text-emerald-400"
                >
                    {{ project.linkLabel }}
                    <ArrowUpRight class="size-3.5" />
                </span>
                <a
                    v-if="project.secondaryUrl"
                    :href="project.secondaryUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    {{ project.secondaryLabel }}
                    <ArrowUpRight class="size-3.5" />
                </a>
                <span
                    v-if="project.isPublicRepo && project.stars > 0"
                    class="inline-flex items-center gap-1"
                >
                    <Star class="size-3.5" />
                    {{ project.stars }}
                </span>
            </div>
        </div>
    </component>
</template>
