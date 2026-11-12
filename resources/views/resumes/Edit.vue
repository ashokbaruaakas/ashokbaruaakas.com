<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import ResumeOverrideField from '@/components/app/portfolio/ResumeOverrideField.vue'
import ResumePreviewPane from '@/components/app/portfolio/ResumePreviewPane.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogDescription, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import resumeRoutes from '@/routes/resumes'
import type { ResumePdfStatus } from '@/types'
import type { Portfolio } from '@/types/portfolio'
import type { ResumeDesign } from '@/views/resume/templates'
import { Link, router, useForm, useHttp } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, ChevronDown, ChevronRight, Download, Maximize2, TriangleAlert } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { computed, onMounted, ref } from 'vue'

type ResumeTemplateOption = { value: string; label: string; description: string; isAtsSafe: boolean; defaultAccent: string; defaultFont: string }
type AccentOption = { value: string; label: string; color: string }
type FontOption = { value: string; label: string }
type SelectionItem = { id: number; label: string; included: boolean }
type TextOverride = { base: string; override: string | null; isStale: boolean }
type HighlightsOverride = { experienceId: number; label: string; base: string[]; override: string[] | null; isStale: boolean }

const props = defineProps<{
    resume: {
        id: number
        name: string
        slug: string
        template: string
        accent: string | null
        font: string | null
        pageCount: number | null
        pdfUrl: string | null
        isPublic: boolean
        isArchived: boolean
        hasSentPdfs: boolean
        jobApplicationId: number | null
    }
    templates: ResumeTemplateOption[]
    accents: AccentOption[]
    fonts: FontOption[]
    selection: Record<string, SelectionItem[]>
    overrides: { headline: TextOverride; summary: TextOverride; highlights: HighlightsOverride[] }
    status: ResumePdfStatus | null
    jobApplications: { id: number; label: string }[]
}>()

const listNames: Record<string, string> = { experience: 'Experience', projects: 'Projects', education: 'Education', skills: 'Skills' }

const form = useForm({
    name: props.resume.name,
    template: props.resume.template,
    accent: props.resume.accent,
    font: props.resume.font,
    job_application_id: props.resume.jobApplicationId,
    selection: Object.fromEntries(Object.entries(props.selection).map(([list, items]) => [list, items.map((item) => ({ ...item }))])),
    overrides: {
        headline: props.overrides.headline.override ?? '',
        summary: props.overrides.summary.override ?? '',
        highlights: Object.fromEntries(props.overrides.highlights.map(({ experienceId, override }) => [experienceId, (override ?? []).join('\n')])),
    },
    reviewed_overrides: [] as string[],
})

type ResumeFormData = ReturnType<typeof form.data>

const NO_JOB = 'none'
const TEMPLATE_DEFAULT = 'default'

const tailoredFor = computed({
    get: () => (form.job_application_id === null ? NO_JOB : String(form.job_application_id)),
    set: (value) => (form.job_application_id = value === NO_JOB ? null : Number(value)),
})

const chosenTemplate = computed(() => props.templates.find(({ value }) => value === form.template) ?? props.templates[0]!)

const accent = computed({
    get: () => form.accent ?? TEMPLATE_DEFAULT,
    set: (value) => (form.accent = value === TEMPLATE_DEFAULT ? null : value),
})

const font = computed({
    get: () => form.font ?? TEMPLATE_DEFAULT,
    set: (value) => (form.font = value === TEMPLATE_DEFAULT ? null : value),
})

const accentLabel = (value: string) => props.accents.find((option) => option.value === value)?.label ?? value
const fontLabel = (value: string) => props.fonts.find((option) => option.value === value)?.label ?? value
const fontChoiceLabel = computed(() =>
    form.font === null ? `Template Default (${fontLabel(chosenTemplate.value.defaultFont)})` : fontLabel(form.font),
)
const accentColor = (value: string) => props.accents.find((option) => option.value === value)?.color

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

const needsReview = (key: string, isStale: boolean) => isStale && !form.reviewed_overrides.includes(key)

const highlightsFor = (experienceId: number) => props.overrides.highlights.find((role) => role.experienceId === experienceId)

const toRequest = (data: ResumeFormData) => ({
    ...data,
    selection: Object.fromEntries(Object.entries(data.selection).map(([list, items]) => [list, items.map(({ id, included }) => ({ id, included }))])),
    overrides: {
        ...data.overrides,
        highlights: Object.fromEntries(Object.entries(data.overrides.highlights).map(([id, text]) => [id, text.split('\n')])),
    },
})

const submit = () => {
    form.transform(toRequest).put(resumeRoutes.update.url(props.resume.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reviewed_overrides = []
            form.defaults()
        },
    })
}

const preview = useHttp<Record<string, never>, { portfolio: Portfolio; design: ResumeDesign }>()
const previewResume = ref<{ portfolio: Portfolio; design: ResumeDesign } | null>(null)
const estimatedPages = ref<number | null>(null)

const refreshPreview = async () => {
    const result = await preview
        .transform(() => toRequest(form.data()))
        .post(resumeRoutes.preview.url(props.resume.id))
        .catch(() => null)

    if (result) {
        previewResume.value = result
    }
}

onMounted(refreshPreview)

watchDebounced(() => form.data(), refreshPreview, { debounce: 400, deep: true })

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

const isFullScreen = ref(false)

const sendsKeptPdfs = computed(() => props.resume.hasSentPdfs)
const isConfirmingRemoval = ref(false)
const isRemoving = ref(false)

const remove = () => {
    router.delete(resumeRoutes.destroy.url(props.resume.id), {
        onStart: () => (isRemoving.value = true),
        onFinish: () => {
            isRemoving.value = false
            isConfirmingRemoval.value = false
        },
    })
}
</script>

<template>
    <div>
        <form class="grid gap-6" @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="resumeRoutes.index.url()" class="text-sm text-muted-foreground hover:text-foreground">Resumes</Link>
                    <span class="text-muted-foreground">/</span>
                    <h1 class="font-display text-xl font-bold tracking-tight">{{ resume.name }}</h1>
                    <Badge v-if="resume.isPublic">Public</Badge>
                    <Badge v-if="resume.isArchived" variant="secondary">Archived</Badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="resume.pdfUrl" type="button" variant="outline" size="sm" as-child>
                        <a :href="resume.pdfUrl"><Download class="size-4" aria-hidden="true" /> Download PDF</a>
                    </Button>
                    <Button v-if="!resume.isPublic" type="button" variant="outline" size="sm" as-child>
                        <Link :href="resumeRoutes.publish.url(resume.id)" method="post" as="button" preserve-scroll>Make Public</Link>
                    </Button>
                    <Button v-if="!resume.isArchived" type="button" variant="outline" size="sm" as-child>
                        <Link :href="resumeRoutes.rebuild.url(resume.id)" method="post" as="button" preserve-scroll>Rebuild Now</Link>
                    </Button>
                    <Button v-if="!resume.isPublic" type="button" variant="outline" size="sm" @click="isConfirmingRemoval = true">
                        {{ sendsKeptPdfs ? 'Archive' : 'Delete' }}
                    </Button>
                </div>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
                <div class="grid gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Name</CardTitle>
                            <CardDescription>
                                <template v-if="resume.pageCount === null">The page count shows after the PDF is built.</template>
                                <template v-else
                                    >The latest PDF is <span class="font-medium text-foreground">{{ pagesLabel(resume.pageCount) }}</span
                                    >.</template
                                >
                                <template v-if="statusLabel"> {{ statusLabel }}</template>
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="resume-name">Name</Label>
                                <Input id="resume-name" v-model="form.name" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="tailored-for">Tailored For</Label>
                                <Select v-model="tailoredFor">
                                    <SelectTrigger id="tailored-for" class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NO_JOB">No Particular Job</SelectItem>
                                        <SelectItem v-for="application in jobApplications" :key="application.id" :value="String(application.id)">{{
                                            application.label
                                        }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-sm text-muted-foreground">When you apply to that job, a copy of this resume's PDF is kept with it.</p>
                                <InputError :message="form.errors.job_application_id" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Design</CardTitle>
                            <CardDescription>The preview follows your choices. Saving rebuilds the PDF right away.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-6">
                            <fieldset class="grid gap-3">
                                <legend class="mb-2 text-sm font-medium">Template</legend>
                                <label
                                    v-for="template in templates"
                                    :key="template.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                >
                                    <input v-model="form.template" type="radio" name="template" :value="template.value" class="mt-1 accent-primary" />
                                    <span class="grid gap-1">
                                        <span class="flex flex-wrap items-center gap-2 font-medium">
                                            {{ template.label }}
                                            <Badge v-if="template.isAtsSafe" variant="secondary">ATS-Safe</Badge>
                                            <Badge v-else variant="outline">Two Columns</Badge>
                                        </span>
                                        <span class="text-sm text-muted-foreground">{{ template.description }}</span>
                                        <span v-if="!template.isAtsSafe" class="text-sm text-muted-foreground"
                                            >A few older applicant tracking systems mix up two columns. Use Classic or Compact for those.</span
                                        >
                                    </span>
                                </label>
                                <InputError :message="form.errors.template" />
                            </fieldset>

                            <fieldset class="grid gap-2">
                                <legend class="mb-2 text-sm font-medium">Colour</legend>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="option in [
                                            {
                                                value: TEMPLATE_DEFAULT,
                                                label: `Template Default (${accentLabel(chosenTemplate.defaultAccent)})`,
                                                color: accentColor(chosenTemplate.defaultAccent),
                                            },
                                            ...accents,
                                        ]"
                                        :key="option.value"
                                        class="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-sm has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                    >
                                        <input v-model="accent" type="radio" name="accent" :value="option.value" class="sr-only" />
                                        <span
                                            class="size-3.5 rounded-full border border-black/10"
                                            :style="{ background: option.color }"
                                            aria-hidden="true"
                                        />
                                        {{ option.label }}
                                    </label>
                                </div>
                                <InputError :message="form.errors.accent" />
                            </fieldset>

                            <div class="grid gap-2">
                                <Label for="resume-font">Font</Label>
                                <Select v-model="font">
                                    <SelectTrigger id="resume-font" class="w-full">
                                        <SelectValue>{{ fontChoiceLabel }}</SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="TEMPLATE_DEFAULT"
                                            >Template Default ({{ fontLabel(chosenTemplate.defaultFont) }})</SelectItem
                                        >
                                        <SelectItem v-for="option in fonts" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.font" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Headline and Summary</CardTitle>
                            <CardDescription>Reword them for this resume only.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-1">
                            <Collapsible
                                v-for="field in ['headline', 'summary'] as const"
                                :key="field"
                                :default-open="needsReview(field, overrides[field].isStale)"
                                class="rounded-md hover:bg-muted/30 data-[state=open]:bg-muted/30"
                            >
                                <CollapsibleTrigger
                                    class="group flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <ChevronRight
                                        class="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-90"
                                        aria-hidden="true"
                                    />
                                    <span class="flex-1 font-medium">{{ field === 'headline' ? 'Headline' : 'Professional Summary' }}</span>
                                    <TriangleAlert
                                        v-if="needsReview(field, overrides[field].isStale)"
                                        class="size-4 text-amber-600 dark:text-amber-400"
                                        aria-label="The portfolio text changed"
                                    />
                                    <Badge v-if="form.overrides[field].trim()" variant="secondary">Reworded</Badge>
                                </CollapsibleTrigger>
                                <CollapsibleContent class="px-2 pb-3">
                                    <ResumeOverrideField
                                        :id="`override-${field}`"
                                        v-model="form.overrides[field]"
                                        :label="field === 'headline' ? 'Headline on This Resume' : 'Summary on This Resume'"
                                        :base="overrides[field].base"
                                        :shows-stale-warning="needsReview(field, overrides[field].isStale)"
                                        :multiline="field === 'summary'"
                                        :error="(form.errors as Record<string, string | undefined>)[`overrides.${field}`]"
                                        @keep-mine="review(field)"
                                    />
                                </CollapsibleContent>
                            </Collapsible>
                        </CardContent>
                    </Card>

                    <Card v-for="(name, list) in listNames" :key="list">
                        <CardHeader>
                            <CardTitle>{{ name }}</CardTitle>
                            <CardDescription>
                                Tick what this resume shows and set the order.<template v-if="list === 'experience'">
                                    Open a role to reword its highlights.</template
                                >
                                <template v-if="resume.isPublic"> New items you add to the portfolio appear here automatically.</template>
                                <template v-else> New items you add to the portfolio stay off this resume until you tick them.</template>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p v-if="!form.selection[list]?.length" class="text-sm text-muted-foreground">Nothing in the portfolio yet.</p>
                            <ul v-else class="grid gap-1">
                                <li v-for="(item, index) in form.selection[list]" :key="item.id">
                                    <Collapsible
                                        :default-open="
                                            list === 'experience' && needsReview(`highlights.${item.id}`, highlightsFor(item.id)?.isStale ?? false)
                                        "
                                        class="rounded-md hover:bg-muted/30 data-[state=open]:bg-muted/30"
                                    >
                                        <div class="flex items-center gap-2 px-2 py-1">
                                            <Checkbox
                                                :id="`${list}-${item.id}`"
                                                :model-value="item.included"
                                                @update:model-value="item.included = Boolean($event)"
                                            />
                                            <label
                                                :for="`${list}-${item.id}`"
                                                class="flex-1 text-sm"
                                                :class="{ 'text-muted-foreground line-through decoration-muted-foreground/40': !item.included }"
                                                >{{ item.label }}</label
                                            >
                                            <template v-if="list === 'experience' && highlightsFor(item.id)">
                                                <TriangleAlert
                                                    v-if="needsReview(`highlights.${item.id}`, highlightsFor(item.id)!.isStale)"
                                                    class="size-4 text-amber-600 dark:text-amber-400"
                                                    aria-label="The portfolio highlights changed"
                                                />
                                                <Badge v-if="form.overrides.highlights[item.id]?.trim()" variant="secondary">Reworded</Badge>
                                                <CollapsibleTrigger as-child>
                                                    <Button type="button" variant="ghost" size="sm" class="group">
                                                        Reword
                                                        <ChevronDown
                                                            class="size-4 transition-transform group-data-[state=open]:rotate-180"
                                                            aria-hidden="true"
                                                        />
                                                    </Button>
                                                </CollapsibleTrigger>
                                            </template>
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
                                        </div>
                                        <CollapsibleContent v-if="list === 'experience' && highlightsFor(item.id)" class="px-2 pb-3 pl-8">
                                            <ResumeOverrideField
                                                :id="`override-highlights-${item.id}`"
                                                v-model="form.overrides.highlights[item.id]!"
                                                label="Highlights on This Resume"
                                                :base="highlightsFor(item.id)!.base"
                                                :shows-stale-warning="needsReview(`highlights.${item.id}`, highlightsFor(item.id)!.isStale)"
                                                multiline
                                                placeholder="One highlight per line"
                                                @keep-mine="review(`highlights.${item.id}`)"
                                            />
                                        </CollapsibleContent>
                                    </Collapsible>
                                </li>
                            </ul>
                            <InputError :message="(form.errors as Record<string, string | undefined>)[`selection.${list}`]" />
                        </CardContent>
                    </Card>
                </div>

                <aside class="grid gap-3 xl:sticky xl:top-4">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="font-medium">Preview</span>
                        <span class="flex items-center gap-2 text-muted-foreground tabular-nums">
                            <template v-if="preview.processing">Updating…</template>
                            <template v-else-if="estimatedPages !== null">{{ pagesLabel(estimatedPages) }} on A4</template>
                            <Button type="button" variant="outline" size="sm" :disabled="!previewResume" @click="isFullScreen = true">
                                <Maximize2 class="size-4" aria-hidden="true" />
                                Full Screen
                            </Button>
                        </span>
                    </div>
                    <ResumePreviewPane
                        v-if="previewResume"
                        :portfolio="previewResume.portfolio"
                        :design="previewResume.design"
                        @estimate="estimatedPages = $event"
                    />
                    <div v-else class="aspect-[210/297] w-full animate-pulse rounded-md bg-muted" />
                    <p class="text-xs text-muted-foreground">Pages break where the PDF breaks. The built PDF has the exact count.</p>
                </aside>
            </div>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>

        <Dialog v-model:open="isFullScreen">
            <DialogScrollContent class="w-[calc(100vw-2rem)] max-w-[calc(210mm+3rem)] sm:max-w-[calc(210mm+3rem)]">
                <DialogHeader>
                    <DialogTitle>{{ form.name || resume.name }}</DialogTitle>
                    <DialogDescription>
                        Your unsaved changes as the PDF would show them<template v-if="estimatedPages !== null"
                            >, {{ pagesLabel(estimatedPages) }} on A4</template
                        >.
                    </DialogDescription>
                </DialogHeader>
                <ResumePreviewPane v-if="previewResume" :portfolio="previewResume.portfolio" :design="previewResume.design" />
            </DialogScrollContent>
        </Dialog>

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
    </div>
</template>
