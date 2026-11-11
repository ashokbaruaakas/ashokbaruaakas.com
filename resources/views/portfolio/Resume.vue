<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import ResumeTemplatePreview from '@/components/app/portfolio/ResumeTemplatePreview.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { formatDate } from '@/lib/helpers'
import assistantRoutes from '@/routes/assistant'
import portfolioRoutes from '@/routes/portfolio'
import type { ResumePdfStatus } from '@/types'
import { TEMPLATE_ESTIMATE_MESSAGE } from '@/views/resume/templates'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, Download, TriangleAlert } from '@lucide/vue'
import { useEventListener } from '@vueuse/core'
import { computed, reactive, ref } from 'vue'

type ResumeTemplateOption = { value: string; label: string; description: string; isAtsSafe: boolean }
type SelectionItem = { id: number; label: string; included: boolean }
type TextOverride = { base: string; override: string | null; isStale: boolean }
type HighlightsOverride = { experienceId: number; label: string; base: string[]; override: string[] | null; isStale: boolean }
type Build = { id: number; builtAt: string | null; template: string | null; pageCount: number | null; sentTo: string[] }

const props = defineProps<{
    resume: {
        id: number
        name: string
        slug: string
        template: string
        pageLimit: number
        pageCount: number | null
        isOverPageLimit: boolean
        isPublic: boolean
        isArchived: boolean
        jobApplicationId: number | null
    }
    templates: ResumeTemplateOption[]
    selection: Record<string, SelectionItem[]>
    overrides: { headline: TextOverride; summary: TextOverride; highlights: HighlightsOverride[] }
    builds: Build[]
    status: ResumePdfStatus | null
    jobApplications: { id: number; label: string }[]
}>()

const listNames: Record<string, string> = { experience: 'Experience', projects: 'Projects', education: 'Education', skills: 'Skills' }

const form = useForm({
    name: props.resume.name,
    template: props.resume.template,
    page_limit: props.resume.pageLimit,
    job_application_id: props.resume.jobApplicationId,
    selection: Object.fromEntries(Object.entries(props.selection).map(([list, items]) => [list, items.map((item) => ({ ...item }))])),
    overrides: {
        headline: props.overrides.headline.override ?? '',
        summary: props.overrides.summary.override ?? '',
        highlights: Object.fromEntries(props.overrides.highlights.map(({ experienceId, override }) => [experienceId, (override ?? []).join('\n')])),
    },
    reviewed_overrides: [] as string[],
})

const pagesLabel = (pages: number) => `${pages} ${pages === 1 ? 'page' : 'pages'}`

const move = (list: string, index: number, offset: number) => {
    const items = form.selection[list]!
    const target = index + offset

    if (target < 0 || target >= items.length) {
        return
    }

    ;[items[index], items[target]] = [items[target]!, items[index]!]
}

const review = (key: string) => {
    if (!form.reviewed_overrides.includes(key)) {
        form.reviewed_overrides.push(key)
    }
}

const isReviewed = (key: string) => form.reviewed_overrides.includes(key)

const submit = () => {
    form.transform((data) => ({
        ...data,
        overrides: {
            ...data.overrides,
            highlights: Object.fromEntries(Object.entries(data.overrides.highlights).map(([id, text]) => [id, text.split('\n')])),
        },
    })).put(portfolioRoutes.resumes.update.url(props.resume.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reviewed_overrides = []
            form.defaults()
        },
    })
}

const estimatedPages = reactive<Record<string, number>>({})

useEventListener(window, 'message', (event: MessageEvent) => {
    if (event.origin === window.location.origin && event.data?.type === TEMPLATE_ESTIMATE_MESSAGE) {
        estimatedPages[event.data.template] = event.data.pages
    }
})

const estimateLabel = (template: string): string => {
    const pages = estimatedPages[template]

    return pages === undefined ? 'Measuring…' : `About ${pagesLabel(pages)}`
}

const chooseTemplate = (template: string) => {
    if (template === props.resume.template) {
        return
    }

    form.template = template
    router.put(
        portfolioRoutes.resumes.update.url(props.resume.id),
        { name: props.resume.name, template, page_limit: props.resume.pageLimit, job_application_id: props.resume.jobApplicationId },
        {
            preserveScroll: true,
            onSuccess: () => form.defaults('template', template),
            onError: () => (form.template = props.resume.template),
        },
    )
}

const trimUrl = computed(() =>
    assistantRoutes.show.url(undefined, {
        query: {
            prompt: `My resume "${props.resume.name}" (id ${props.resume.id}) is ${props.resume.pageCount} pages, over its limit of ${props.resume.pageLimit}. Propose changes that bring it within the limit without losing my strongest results.`,
        },
    }),
)

const statusLabel = computed(() => {
    switch (props.status?.status) {
        case 'scheduled':
            return 'A new PDF is scheduled.'
        case 'building':
            return 'Building the PDF…'
        case 'failed':
            return 'The last build failed.'
        case 'missing':
            return 'No PDF yet.'
        default:
            return null
    }
})

const isConfirmingRemoval = ref(false)
const isRemoving = ref(false)
const sendsKeptPdfs = computed(() => props.builds.some(({ sentTo }) => sentTo.length > 0))

const remove = () => {
    router.delete(portfolioRoutes.resumes.destroy.url(props.resume.id), {
        onStart: () => (isRemoving.value = true),
        onFinish: () => {
            isRemoving.value = false
            isConfirmingRemoval.value = false
        },
    })
}
</script>

<template>
    <PortfolioLayout>
        <form class="grid gap-6" @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="portfolioRoutes.resumes.index.url()" class="text-sm text-muted-foreground hover:text-foreground">Resumes</Link>
                    <span class="text-muted-foreground">/</span>
                    <h2 class="text-lg font-semibold">{{ resume.name }}</h2>
                    <Badge v-if="resume.isPublic">Public</Badge>
                    <Badge v-if="resume.isArchived" variant="secondary">Archived</Badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="!resume.isPublic" type="button" variant="outline" size="sm" as-child>
                        <Link :href="portfolioRoutes.resumes.publish.url(resume.id)" method="post" as="button" preserve-scroll>Make Public</Link>
                    </Button>
                    <Button v-if="!resume.isArchived" type="button" variant="outline" size="sm" as-child>
                        <Link :href="portfolioRoutes.resumes.rebuild.url(resume.id)" method="post" as="button" preserve-scroll>Rebuild Now</Link>
                    </Button>
                    <Button v-if="!resume.isPublic" type="button" variant="outline" size="sm" @click="isConfirmingRemoval = true">
                        {{ sendsKeptPdfs ? 'Archive' : 'Delete' }}
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Name and Length</CardTitle>
                    <CardDescription>
                        <template v-if="resume.pageCount === null">The page count shows after the PDF is built.</template>
                        <template v-else>
                            The latest PDF is <span class="font-medium text-foreground">{{ pagesLabel(resume.pageCount) }}</span
                            ><template v-if="resume.isOverPageLimit">, over the limit</template>.
                        </template>
                        <template v-if="statusLabel"> {{ statusLabel }}</template>
                        <Link v-if="resume.isOverPageLimit" :href="trimUrl" class="ml-1 underline underline-offset-4">Ask the Assistant to Trim</Link>
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-[1fr_auto]">
                    <div class="grid gap-2">
                        <Label for="resume-name">Name</Label>
                        <Input id="resume-name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="page-limit">Page Limit</Label>
                        <Input id="page-limit" v-model.number="form.page_limit" type="number" min="1" max="5" class="w-24" required />
                        <InputError :message="form.errors.page_limit" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="tailored-for">Tailored For</Label>
                        <select
                            id="tailored-for"
                            v-model="form.job_application_id"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option :value="null">No particular job</option>
                            <option v-for="application in jobApplications" :key="application.id" :value="application.id">
                                {{ application.label }}
                            </option>
                        </select>
                        <p class="text-sm text-muted-foreground">When you apply to that job, this resume's PDF is recorded as the one you sent.</p>
                        <InputError :message="form.errors.job_application_id" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Template</CardTitle>
                    <CardDescription>Choosing one saves it and rebuilds the PDF right away.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <label
                        v-for="template in templates"
                        :key="template.value"
                        class="grid cursor-pointer gap-3 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                    >
                        <ResumeTemplatePreview
                            :src="portfolioRoutes.resumes.preview.url({ resume: resume.id, template: template.value })"
                            :title="`${template.label} template preview`"
                        />
                        <span class="flex items-start gap-2">
                            <input
                                type="radio"
                                name="template"
                                :value="template.value"
                                :checked="form.template === template.value"
                                class="mt-1 accent-primary"
                                @change="chooseTemplate(template.value)"
                            />
                            <span class="grid gap-1">
                                <span class="flex flex-wrap items-center gap-2 font-medium">
                                    {{ template.label }}
                                    <Badge v-if="template.isAtsSafe" variant="secondary">ATS-Safe</Badge>
                                    <span class="text-sm font-normal text-muted-foreground tabular-nums">{{ estimateLabel(template.value) }}</span>
                                </span>
                                <span class="text-sm text-muted-foreground">{{ template.description }}</span>
                            </span>
                        </span>
                    </label>
                </CardContent>
            </Card>

            <Card v-for="(name, list) in listNames" :key="list">
                <CardHeader>
                    <CardTitle>{{ name }}</CardTitle>
                    <CardDescription>
                        Tick what this resume shows and set the order.
                        <template v-if="resume.isPublic">New items you add to the portfolio appear here automatically.</template>
                        <template v-else>New items you add to the portfolio stay off this resume until you tick them.</template>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p v-if="!form.selection[list]?.length" class="text-sm text-muted-foreground">Nothing in the portfolio yet.</p>
                    <ul v-else class="grid gap-1">
                        <li
                            v-for="(item, index) in form.selection[list]"
                            :key="item.id"
                            class="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-muted/50"
                        >
                            <input :id="`${list}-${item.id}`" v-model="item.included" type="checkbox" class="accent-primary" />
                            <label :for="`${list}-${item.id}`" class="flex-1 text-sm" :class="{ 'text-muted-foreground': !item.included }">{{
                                item.label
                            }}</label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === 0"
                                :aria-label="`Move ${item.label} up`"
                                @click="move(list, index, -1)"
                            >
                                <ArrowUp class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === form.selection[list]!.length - 1"
                                :aria-label="`Move ${item.label} down`"
                                @click="move(list, index, 1)"
                            >
                                <ArrowDown class="size-4" />
                            </Button>
                        </li>
                    </ul>
                    <InputError :message="(form.errors as Record<string, string | undefined>)[`selection.${list}`]" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Rewording</CardTitle>
                    <CardDescription
                        >Replace the headline, the summary or a role's highlights on this resume only. Leave a box empty to use the portfolio
                        text.</CardDescription
                    >
                </CardHeader>
                <CardContent class="grid gap-6">
                    <div v-for="field in ['headline', 'summary'] as const" :key="field" class="grid gap-2">
                        <Label :for="`override-${field}`">{{ field === 'headline' ? 'Headline' : 'Professional Summary' }}</Label>
                        <p class="text-sm text-muted-foreground">Portfolio: {{ overrides[field].base || 'empty' }}</p>
                        <p
                            v-if="overrides[field].isStale && !isReviewed(field)"
                            class="flex flex-wrap items-center gap-2 rounded-md bg-amber-500/10 px-2.5 py-1.5 text-sm text-amber-700 dark:text-amber-400"
                        >
                            <TriangleAlert class="size-4" aria-hidden="true" />
                            The portfolio text changed since you wrote this.
                            <Button type="button" variant="link" size="sm" class="h-auto p-0" @click="review(field)">Keep Mine</Button>
                            <Button type="button" variant="link" size="sm" class="h-auto p-0" @click="form.overrides[field] = ''"
                                >Use the Portfolio Text</Button
                            >
                        </p>
                        <Textarea v-if="field === 'summary'" :id="`override-${field}`" v-model="form.overrides.summary" rows="4" />
                        <Input v-else :id="`override-${field}`" v-model="form.overrides.headline" />
                        <InputError :message="(form.errors as Record<string, string | undefined>)[`overrides.${field}`]" />
                    </div>

                    <div v-for="role in overrides.highlights" :key="role.experienceId" class="grid gap-2">
                        <Label :for="`override-highlights-${role.experienceId}`">Highlights: {{ role.label }}</Label>
                        <details class="text-sm text-muted-foreground">
                            <summary class="cursor-pointer">Portfolio highlights</summary>
                            <ul class="mt-1 list-disc pl-5">
                                <li v-for="highlight in role.base" :key="highlight">{{ highlight }}</li>
                            </ul>
                        </details>
                        <p
                            v-if="role.isStale && !isReviewed(`highlights.${role.experienceId}`)"
                            class="flex flex-wrap items-center gap-2 rounded-md bg-amber-500/10 px-2.5 py-1.5 text-sm text-amber-700 dark:text-amber-400"
                        >
                            <TriangleAlert class="size-4" aria-hidden="true" />
                            The portfolio highlights changed since you wrote these.
                            <Button type="button" variant="link" size="sm" class="h-auto p-0" @click="review(`highlights.${role.experienceId}`)"
                                >Keep Mine</Button
                            >
                            <Button
                                type="button"
                                variant="link"
                                size="sm"
                                class="h-auto p-0"
                                @click="form.overrides.highlights[role.experienceId] = ''"
                            >
                                Use the Portfolio Text
                            </Button>
                        </p>
                        <Textarea
                            :id="`override-highlights-${role.experienceId}`"
                            v-model="form.overrides.highlights[role.experienceId]"
                            rows="3"
                            placeholder="One highlight per line"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>PDF History</CardTitle>
                    <CardDescription
                        >A new entry is added each time the content changes. The last 10 are kept, plus every PDF sent with a job.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <p v-if="!builds.length" class="text-sm text-muted-foreground">No PDF yet.</p>
                    <ul v-else class="grid gap-2">
                        <li
                            v-for="build in builds"
                            :key="build.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                        >
                            <span class="grid gap-0.5">
                                <span
                                    >{{ build.builtAt ? formatDate(build.builtAt, { dateStyle: 'medium', timeStyle: 'short' }) : 'Unknown date' }} ·
                                    {{ build.template }}
                                    <template v-if="build.pageCount !== null"> · {{ pagesLabel(build.pageCount) }}</template></span
                                >
                                <span v-if="build.sentTo.length" class="text-muted-foreground">Sent to {{ build.sentTo.join(', ') }}</span>
                            </span>
                            <Button type="button" variant="ghost" size="sm" as-child>
                                <a :href="portfolioRoutes.resumeBuilds.download.url(build.id)"
                                    ><Download class="size-4" aria-hidden="true" /> Download</a
                                >
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>

        <ConfirmDialog
            v-model:open="isConfirmingRemoval"
            :title="sendsKeptPdfs ? `Archive ${resume.name}?` : `Delete ${resume.name}?`"
            :description="
                sendsKeptPdfs
                    ? 'It stops being rebuilt and leaves the list. The PDFs you sent with jobs are kept.'
                    : 'Its settings and PDFs are removed. This can\'t be undone.'
            "
            :confirm-label="sendsKeptPdfs ? 'Archive' : 'Delete'"
            :processing="isRemoving"
            @confirm="remove"
        />
    </PortfolioLayout>
</template>
