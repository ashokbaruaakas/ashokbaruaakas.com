<script setup lang="ts">
import { chanceClass, formatDay } from '@/components/app/job-hunt/jobHunt'
import StatusBadge from '@/components/app/job-hunt/StatusBadge.vue'
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import { cn } from '@/lib/utils'
import jobHuntRoutes from '@/routes/job-hunt'
import resumeRoutes from '@/routes/resumes'
import type {
    JobApplication,
    JobApplicationEvent,
    JobApplicationStatusChoice,
    JobChanceOption,
    JobRelocationOption,
    ResumeBuildChoice,
    WorkModeOption,
} from '@/types/jobHunt'
import { Link, router, useForm, useHttp } from '@inertiajs/vue3'
import { ExternalLink, FileText, LoaderCircle, MessageSquare, Sparkles, Trash2 } from '@lucide/vue'
import { computed, ref, watch } from 'vue'

interface JobPostDetails {
    company: string | null
    role: string | null
    country: string | null
    location: string | null
    work_mode: string | null
    relocation: string | null
    salary: string | null
    deadline: string | null
    link: string | null
    summary: string | null
}

const postFields = ['company', 'role', 'country', 'location', 'work_mode', 'relocation', 'salary', 'deadline', 'link'] as const

type DetailField =
    | 'company'
    | 'role'
    | 'chance_note'
    | 'next_step'
    | 'next_step_on'
    | 'country'
    | 'location'
    | 'applied_on'
    | 'source'
    | 'salary'
    | 'contact'
    | 'deadline'
    | 'link'
    | 'notes'

const props = defineProps<{
    huntId: number
    application: JobApplication | null
    statuses: JobApplicationStatusChoice[]
    chances: JobChanceOption[]
    workModes: WorkModeOption[]
    relocations: JobRelocationOption[]
    preferredWorkModes: string[]
    newStatus: string
    today: string
    resumeBuilds: ResumeBuildChoice[]
    tailoredResumes: { id: number; name: string; jobApplicationId: number }[]
    publicResumeName: string | null
}>()

const open = defineModel<boolean>('open', { required: true })

const isNew = computed(() => props.application === null)

const fields: {
    name: DetailField
    label: string
    type?: 'date' | 'url' | 'textarea'
    wide?: boolean
    placeholder?: string
}[] = [
    {
        name: 'next_step',
        label: 'What Happens Next',
        placeholder: 'Technical interview',
    },
    { name: 'next_step_on', label: 'When', type: 'date' },
    {
        name: 'country',
        label: 'Country',
        placeholder: 'Germany, or EU only if remote',
    },
    { name: 'location', label: 'City', placeholder: 'Berlin' },
    { name: 'applied_on', label: 'Applied On', type: 'date' },
    { name: 'source', label: 'Found Via', placeholder: 'LinkedIn, referral…' },
    { name: 'salary', label: 'Salary' },
    { name: 'contact', label: 'Contact' },
    { name: 'deadline', label: 'Apply By', type: 'date' },
    {
        name: 'link',
        label: 'Job Post Link',
        type: 'url',
        wide: true,
        placeholder: 'https://',
    },
    { name: 'notes', label: 'Notes', type: 'textarea', wide: true },
]

const valuesFrom = (application: JobApplication | null) => ({
    status: application?.status ?? props.newStatus,
    company: application?.company ?? '',
    role: application?.role ?? '',
    chance: application?.chance ?? null,
    chance_note: application?.chance_note ?? '',
    next_step: application?.next_step ?? '',
    next_step_on: application?.next_step_on ?? '',
    country: application?.country ?? '',
    location: application?.location ?? '',
    work_mode: application?.work_mode ?? null,
    relocation: application?.relocation ?? null,
    applied_on: application?.applied_on ?? '',
    source: application?.source ?? '',
    salary: application?.salary ?? '',
    contact: application?.contact ?? '',
    deadline: application?.deadline ?? '',
    link: application?.link ?? '',
    notes: application?.notes ?? '',
})

const form = useForm(valuesFrom(null))
const postReader = useHttp<{ text: string }, JobPostDetails>({ text: '' })
const isPastingPost = ref(false)
const noteForm = useForm({ body: '', occurred_on: props.today })
const isConfirmingRemoval = ref(false)
const isRemoving = ref(false)

const loadForm = () => {
    form.clearErrors()
    form.defaults(valuesFrom(props.application))
    form.reset()
    noteForm.reset()
    noteForm.clearErrors()
    postReader.reset()
    postReader.clearErrors()
    isPastingPost.value = false
}

const readPost = async () => {
    const details = await postReader.post(jobHuntRoutes.readPost.url()).catch(() => null)

    if (!details) {
        return
    }

    for (const field of postFields) {
        if (details[field] && !form[field]) {
            form[field] = details[field]
        }
    }

    if (details.summary && !form.notes) {
        form.notes = details.summary
    }

    postReader.reset()
    isPastingPost.value = false
}

watch(
    () => [open.value, props.application?.id],
    () => {
        if (open.value) {
            loadForm()
        }
    },
)

const statusLabel = (status: string | null): string => props.statuses.find(({ value }) => value === status)?.label ?? status ?? ''

const describeEvent = (event: JobApplicationEvent): string => {
    if (event.type === 'note') {
        return event.body ?? ''
    }

    return event.from_status ? `${statusLabel(event.from_status)} → ${statusLabel(event.to_status)}` : `Added as ${statusLabel(event.to_status)}`
}

const visitOptions = { preserveScroll: true, preserveState: true }

const save = () => {
    const payload = form.transform(({ status, ...details }) => (isNew.value ? { status, ...details } : details))

    if (props.application) {
        payload.put(
            jobHuntRoutes.applications.update.url({
                jobHunt: props.huntId,
                application: props.application.id,
            }),
            {
                ...visitOptions,
                onSuccess: () => form.defaults(),
            },
        )
    } else {
        payload.post(jobHuntRoutes.applications.store.url(props.huntId), {
            ...visitOptions,
            onSuccess: () => (open.value = false),
        })
    }
}

const changeStatus = (status: string) => {
    if (!props.application || status === props.application.status) {
        return
    }

    router.put(
        jobHuntRoutes.applications.status.update.url({
            jobHunt: props.huntId,
            application: props.application.id,
        }),
        { status },
        {
            ...visitOptions,
            onSuccess: () => {
                if (!form.applied_on && props.application?.applied_on) {
                    form.applied_on = props.application.applied_on
                    form.defaults('applied_on', props.application.applied_on)
                }
            },
        },
    )
}

const sentResume = computed(() => props.resumeBuilds.find(({ id }) => id === props.application?.resume_build_id) ?? null)

const NOT_RECORDED = 'none'

const saveSentResume = (value: string) => {
    if (!props.application) {
        return
    }

    router.put(
        jobHuntRoutes.applications.sentResume.update.url({ jobHunt: props.huntId, application: props.application.id }),
        { resume_build_id: value === NOT_RECORDED ? null : Number(value) },
        { preserveScroll: true },
    )
}

const toggleChance = (chance: string) => (form.chance = form.chance === chance ? null : chance)

const toggleWorkMode = (workMode: string) => (form.work_mode = form.work_mode === workMode ? null : workMode)

const currentStatus = computed(() => props.application?.status ?? form.status)

const hasApplied = computed(() => !['wishlist', 'ignored'].includes(currentStatus.value))

const visibleFields = computed(() => fields.filter(({ name }) => name !== 'applied_on' || hasApplied.value))

const isOutsidePreferredWorkModes = computed(
    () => form.work_mode !== null && props.preferredWorkModes.length > 0 && !props.preferredWorkModes.includes(form.work_mode),
)

const relocationChoices = computed(() => [
    ...props.relocations.map(({ value, label }) => ({
        value: String(value) as string | null,
        label,
    })),
    { value: null, label: 'Not Mentioned' },
])

const addNote = () => {
    if (!props.application) {
        return
    }

    noteForm.post(
        jobHuntRoutes.applications.notes.store.url({
            jobHunt: props.huntId,
            application: props.application.id,
        }),
        {
            ...visitOptions,
            onSuccess: () => noteForm.reset('body'),
        },
    )
}

const removeNote = (event: JobApplicationEvent) => {
    if (!props.application) {
        return
    }

    router.delete(
        jobHuntRoutes.applications.notes.destroy.url({
            jobHunt: props.huntId,
            application: props.application.id,
            event: event.id,
        }),
        visitOptions,
    )
}

const tailoredResume = computed(() => props.tailoredResumes.find(({ jobApplicationId }) => jobApplicationId === props.application?.id) ?? null)
const isConfirmingTailoring = ref(false)
const isTailoring = ref(false)

const tailorResume = () => {
    if (!props.application) {
        return
    }

    router.post(
        jobHuntRoutes.applications.resume.store.url({ jobHunt: props.huntId, application: props.application.id }),
        {},
        {
            onStart: () => (isTailoring.value = true),
            onFinish: () => {
                isTailoring.value = false
                isConfirmingTailoring.value = false
            },
        },
    )
}

const remove = () => {
    if (!props.application) {
        return
    }

    router.delete(
        jobHuntRoutes.applications.destroy.url({
            jobHunt: props.huntId,
            application: props.application.id,
        }),
        {
            ...visitOptions,
            onStart: () => (isRemoving.value = true),
            onSuccess: () => (open.value = false),
            onFinish: () => {
                isRemoving.value = false
                isConfirmingRemoval.value = false
            },
        },
    )
}
</script>

<template>
    <div>
        <Sheet v-model:open="open">
            <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
                <form class="flex min-h-full shrink-0 flex-col" @submit.prevent="save">
                    <SheetHeader>
                        <SheetTitle>{{ application ? application.company : 'Add a Job' }}</SheetTitle>
                        <SheetDescription>{{ application ? application.role : 'Add a job you applied for or want to apply for.' }}</SheetDescription>
                        <Button v-if="application" as-child variant="outline" size="sm" class="mt-2 self-start">
                            <Link
                                :href="
                                    jobHuntRoutes.applications.chat.url({
                                        jobHunt: huntId,
                                        application: application.id,
                                    })
                                "
                            >
                                <MessageSquare />
                                Discuss With Assistant
                            </Link>
                        </Button>
                        <Button v-if="application && tailoredResume" as-child variant="outline" size="sm" class="self-start">
                            <Link :href="resumeRoutes.edit.url(tailoredResume.id)">
                                <FileText />
                                Open Tailored Resume
                            </Link>
                        </Button>
                        <Button
                            v-else-if="application && publicResumeName"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="self-start"
                            @click="isConfirmingTailoring = true"
                        >
                            <FileText />
                            Tailor a Resume
                        </Button>
                    </SheetHeader>

                    <div class="grid flex-1 content-start gap-8 px-4 py-2">
                        <section v-if="isNew" class="grid gap-3 rounded-lg border border-dashed p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="grid gap-1">
                                    <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Fill From a Job Post</h3>
                                    <p class="text-sm text-muted-foreground">Paste the post and the assistant fills in the empty fields.</p>
                                </div>
                                <Button v-if="!isPastingPost" type="button" variant="outline" size="sm" @click="isPastingPost = true">
                                    <Sparkles />
                                    Paste Post
                                </Button>
                            </div>
                            <template v-if="isPastingPost">
                                <Label for="job-post" class="sr-only">Job Post</Label>
                                <Textarea
                                    id="job-post"
                                    v-model="postReader.text"
                                    rows="6"
                                    maxlength="20000"
                                    placeholder="Paste the text of the job post"
                                />
                                <InputError :message="postReader.errors.text" />
                                <div class="flex justify-end gap-2">
                                    <Button type="button" variant="ghost" size="sm" @click="isPastingPost = false">Cancel</Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        :disabled="postReader.processing || postReader.text.trim().length < 50"
                                        @click="readPost"
                                    >
                                        <LoaderCircle v-if="postReader.processing" class="animate-spin" />
                                        <Sparkles v-else />
                                        Fill In Details
                                    </Button>
                                </div>
                            </template>
                        </section>

                        <section class="grid gap-3">
                            <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</h3>
                            <div class="flex flex-wrap gap-1.5" role="group" aria-label="Status">
                                <button
                                    v-for="status in statuses"
                                    :key="status.value"
                                    type="button"
                                    :aria-pressed="(application?.status ?? form.status) === status.value"
                                    class="cursor-pointer rounded-full focus-visible:ring-3 focus-visible:ring-ring/50 focus-visible:outline-none"
                                    @click="application ? changeStatus(status.value) : (form.status = status.value)"
                                >
                                    <StatusBadge
                                        :status="status.value"
                                        :label="status.label"
                                        :muted="(application?.status ?? form.status) !== status.value"
                                        :class="
                                            (application?.status ?? form.status) === status.value &&
                                            'ring-2 ring-foreground/70 ring-offset-1 ring-offset-background'
                                        "
                                    />
                                </button>
                            </div>
                            <p v-if="application" class="text-sm text-muted-foreground">
                                Changing the status saves it right away and adds it to the timeline.
                            </p>
                            <InputError :message="form.errors.status" />
                        </section>

                        <section class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="job-company">Company<span aria-hidden="true"> *</span></Label>
                                <Input id="job-company" v-model="form.company" maxlength="255" required />
                                <InputError :message="form.errors.company" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="job-role">Role<span aria-hidden="true"> *</span></Label>
                                <Input id="job-role" v-model="form.role" maxlength="255" required />
                                <InputError :message="form.errors.role" />
                            </div>
                        </section>

                        <section class="grid gap-3">
                            <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Chance of Getting It</h3>
                            <div class="flex flex-wrap gap-1.5" role="group" aria-label="Chance of Getting It">
                                <button
                                    v-for="chance in chances"
                                    :key="chance.value"
                                    type="button"
                                    :aria-pressed="form.chance === chance.value"
                                    :class="
                                        cn(
                                            'cursor-pointer rounded-full border px-3 py-1 text-sm font-medium transition-colors',
                                            form.chance === chance.value
                                                ? cn('border-transparent', chanceClass(String(chance.value)))
                                                : 'text-muted-foreground hover:text-foreground',
                                        )
                                    "
                                    @click="toggleChance(String(chance.value))"
                                >
                                    {{ chance.label }}
                                </button>
                            </div>
                            <div class="grid gap-2">
                                <Label for="job-chance_note">Why This Rating</Label>
                                <Input id="job-chance_note" v-model="form.chance_note" maxlength="255" />
                                <InputError :message="form.errors.chance_note ?? form.errors.chance" />
                            </div>
                        </section>

                        <section class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <h3 id="job-work-mode" class="text-sm font-medium">Work Mode</h3>
                                <div class="flex flex-wrap items-start gap-1.5" role="group" aria-labelledby="job-work-mode">
                                    <button
                                        v-for="workMode in workModes"
                                        :key="workMode.value"
                                        type="button"
                                        :aria-pressed="form.work_mode === workMode.value"
                                        :class="
                                            cn(
                                                'cursor-pointer rounded-full border px-3 py-1 text-sm font-medium transition-colors',
                                                form.work_mode === workMode.value
                                                    ? 'border-transparent bg-foreground text-background'
                                                    : 'text-muted-foreground hover:text-foreground',
                                            )
                                        "
                                        @click="toggleWorkMode(String(workMode.value))"
                                    >
                                        {{ workMode.label }}
                                    </button>
                                </div>
                                <p v-if="isOutsidePreferredWorkModes" class="text-sm text-amber-700 dark:text-amber-400">
                                    Not a work mode this hunt is looking for.
                                </p>
                                <InputError :message="form.errors.work_mode" />
                            </div>
                            <div class="grid gap-2">
                                <h3 id="job-relocation" class="text-sm font-medium">Relocation Help</h3>
                                <div class="flex flex-wrap items-start gap-1.5" role="group" aria-labelledby="job-relocation">
                                    <button
                                        v-for="relocation in relocationChoices"
                                        :key="relocation.value ?? 'none'"
                                        type="button"
                                        :aria-pressed="form.relocation === relocation.value"
                                        :class="
                                            cn(
                                                'cursor-pointer rounded-full border px-3 py-1 text-sm font-medium transition-colors',
                                                form.relocation === relocation.value
                                                    ? 'border-transparent bg-foreground text-background'
                                                    : 'text-muted-foreground hover:text-foreground',
                                            )
                                        "
                                        @click="form.relocation = relocation.value"
                                    >
                                        {{ relocation.label }}
                                    </button>
                                </div>
                                <InputError :message="form.errors.relocation" />
                            </div>
                        </section>

                        <section class="grid gap-4 sm:grid-cols-2">
                            <div v-for="field in visibleFields" :key="field.name" :class="cn('grid gap-2', field.wide && 'sm:col-span-2')">
                                <Label :for="`job-${field.name}`" class="flex items-center gap-2">
                                    {{ field.label }}
                                    <a
                                        v-if="field.type === 'url' && application?.link"
                                        :href="application.link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-xs font-normal text-primary underline-offset-4 hover:underline"
                                    >
                                        Open <ExternalLink class="size-3" />
                                    </a>
                                </Label>
                                <Textarea v-if="field.type === 'textarea'" :id="`job-${field.name}`" v-model="form[field.name]" rows="4" />
                                <Input
                                    v-else
                                    :id="`job-${field.name}`"
                                    v-model="form[field.name]"
                                    :type="field.type ?? 'text'"
                                    :placeholder="field.placeholder"
                                />
                                <InputError :message="form.errors[field.name]" />
                            </div>
                        </section>

                        <section v-if="application" class="grid gap-2">
                            <Label for="job-sent-resume">Resume Sent</Label>
                            <div class="flex items-center gap-2">
                                <Select
                                    :model-value="application.resume_build_id === null ? NOT_RECORDED : String(application.resume_build_id)"
                                    @update:model-value="saveSentResume(String($event))"
                                >
                                    <SelectTrigger id="job-sent-resume" class="flex-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NOT_RECORDED">Not Recorded</SelectItem>
                                        <SelectItem v-for="build in resumeBuilds" :key="build.id" :value="String(build.id)">{{
                                            build.label
                                        }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button v-if="sentResume" type="button" variant="outline" size="sm" as-child>
                                    <a :href="sentResume.downloadUrl">Download</a>
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Recorded when you apply: the PDF of the resume tailored for this job, or else your public one.
                            </p>
                        </section>
                    </div>

                    <SheetFooter class="flex-row items-center justify-between gap-2">
                        <Button
                            v-if="application"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="isConfirmingRemoval = true"
                        >
                            <Trash2 /> Remove Job
                        </Button>
                        <span v-else />
                        <Button type="submit" :disabled="form.processing || (!isNew && !form.isDirty)">{{
                            isNew ? 'Add Job' : 'Save Details'
                        }}</Button>
                    </SheetFooter>
                </form>

                <section v-if="application" class="grid shrink-0 gap-4 border-t px-4 pt-6 pb-8">
                    <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Timeline</h3>
                    <form class="grid gap-2" @submit.prevent="addNote">
                        <Label for="job-note" class="sr-only">What happened</Label>
                        <div class="flex gap-2">
                            <Input
                                id="job-note"
                                v-model="noteForm.body"
                                placeholder="Log what happened, e.g. HR call booked"
                                maxlength="2000"
                                class="flex-1"
                            />
                            <Input v-model="noteForm.occurred_on" type="date" :max="today" aria-label="Date" class="w-36 shrink-0" />
                            <Button type="submit" variant="outline" :disabled="noteForm.processing || !noteForm.body.trim()">Log</Button>
                        </div>
                        <InputError :message="noteForm.errors.body ?? noteForm.errors.occurred_on" />
                    </form>
                    <ol class="ml-1 grid border-l-2 border-border">
                        <li v-for="event in application.events" :key="event.id" class="relative flex items-start justify-between gap-3 pb-4 pl-4">
                            <span
                                :class="
                                    cn(
                                        'absolute top-1.5 -left-[7px] size-3 rounded-full border-2 bg-background',
                                        event.type === 'note' ? 'border-muted-foreground' : 'border-primary',
                                    )
                                "
                            />
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-muted-foreground">
                                    {{ formatDay(event.occurred_on) }}
                                </p>
                                <p class="text-sm break-words whitespace-pre-wrap">
                                    {{ describeEvent(event) }}
                                </p>
                            </div>
                            <Button
                                v-if="event.type === 'note'"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-7 shrink-0"
                                aria-label="Remove note"
                                @click="removeNote(event)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </li>
                    </ol>
                </section>
            </SheetContent>
        </Sheet>

        <ConfirmDialog
            v-model:open="isConfirmingTailoring"
            :title="`Tailor a Resume for ${application?.company ?? 'This Job'}?`"
            :description="`This makes a copy of your public resume, ${publicResumeName}, tailored for this job, and opens it with the assistant beside it. Your public resume stays as it is.`"
            confirm-label="Make the Copy"
            confirm-variant="default"
            :processing="isTailoring"
            @confirm="tailorResume"
        />

        <ConfirmDialog
            v-model:open="isConfirmingRemoval"
            :title="`Remove ${application?.company ?? 'this job'}?`"
            description="This removes the job and its timeline. It can't be undone."
            confirm-label="Remove"
            :processing="isRemoving"
            @confirm="remove"
        />
    </div>
</template>
