<script setup lang="ts">
import { Button } from '@/components/ui/button'
import assistantRoutes from '@/routes/assistant'
import resumeRoutes from '@/routes/resumes'
import { Link, usePage, usePoll } from '@inertiajs/vue3'
import { FileText, LoaderCircle, TriangleAlert } from '@lucide/vue'
import { useIntervalFn } from '@vueuse/core'
import { computed, onMounted, ref, watch } from 'vue'

const page = usePage()
const resumePdf = computed(() => page.props.resumePdf)

const now = ref<number | null>(null)

onMounted(() => {
    now.value = Date.now()
})
useIntervalFn(() => {
    now.value = Date.now()
}, 1000)

const secondsRemaining = computed(() => {
    if (now.value === null || !resumePdf.value?.scheduledFor) {
        return null
    }

    return Math.max(0, Math.ceil((new Date(resumePdf.value.scheduledFor).getTime() - now.value) / 1000))
})

const countdown = computed(() => {
    const seconds = secondsRemaining.value ?? 0

    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`
})

const trimPrompt = computed(
    () =>
        `My public resume PDF is ${resumePdf.value?.pageCount} pages, over my limit of ${resumePdf.value?.pageLimit}. Propose changes that bring it within the limit without losing my strongest results.`,
)

const isBuilding = computed(() => resumePdf.value?.status === 'building' || (resumePdf.value?.status === 'scheduled' && secondsRemaining.value === 0))

const { start, stop } = usePoll(3000, { only: ['resumePdf'] }, { autoStart: false })

onMounted(() => {
    watch(isBuilding, (building) => (building ? start() : stop()), { immediate: true })
})
</script>

<template>
    <div
        v-if="resumePdf"
        role="status"
        class="flex min-h-11 flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b bg-muted/50 px-4 py-1.5 text-sm sm:px-6"
    >
        <p v-if="isBuilding" class="flex items-center gap-2">
            <LoaderCircle class="size-4 animate-spin text-muted-foreground" aria-hidden="true" />
            Rebuilding your resume PDF…
        </p>
        <p v-else-if="resumePdf.status === 'scheduled'" class="flex items-center gap-2">
            <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
            <span>
                Resume PDF rebuild scheduled<span v-if="secondsRemaining !== null" aria-hidden="true">
                    in <span class="tabular-nums">{{ countdown }}</span></span
                >
            </span>
        </p>
        <p v-else-if="resumePdf.status === 'failed'" class="flex items-center gap-2">
            <TriangleAlert class="size-4 text-destructive" aria-hidden="true" />
            The resume PDF rebuild failed. Visitors still get the previous version.
        </p>
        <p v-else-if="resumePdf.status === 'over_page_limit'" class="flex items-center gap-2">
            <TriangleAlert class="size-4 text-amber-600 dark:text-amber-400" aria-hidden="true" />
            Your resume PDF is {{ resumePdf.pageCount }} pages, over your limit of {{ resumePdf.pageLimit }}.
        </p>
        <p v-else class="flex items-center gap-2">
            <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
            Your resume PDF hasn't been built yet.
        </p>

        <div v-if="resumePdf.status === 'over_page_limit'" class="flex gap-2">
            <Button variant="outline" size="sm" as-child>
                <Link :href="assistantRoutes.show.url(undefined, { query: { prompt: trimPrompt } })">Ask the Assistant to Trim</Link>
            </Button>
            <Button variant="outline" size="sm" as-child>
                <Link :href="resumeRoutes.public.edit.url()">Resume Settings</Link>
            </Button>
        </div>
        <Button v-else-if="!isBuilding" variant="outline" size="sm" as-child>
            <Link :href="resumeRoutes.public.rebuild.url()" method="post" as="button" preserve-scroll>
                {{ resumePdf.status === 'scheduled' ? 'Rebuild Now' : resumePdf.status === 'failed' ? 'Try Again' : 'Build Now' }}
            </Link>
        </Button>
    </div>
</template>
