<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { formatDate } from '@/lib/helpers'
import resumeRoutes from '@/routes/resumes'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type ResumeSummary = {
    id: number
    name: string
    template: string
    pageCount: number | null
    isPublic: boolean
    isArchived: boolean
    tailoredFor: string | null
    builtAt: string | null
}

const props = defineProps<{
    resumes: ResumeSummary[]
}>()

const activeResumes = computed(() => props.resumes.filter(({ isArchived }) => !isArchived))
const archivedResumes = computed(() => props.resumes.filter(({ isArchived }) => isArchived))

const EVERYTHING = 'everything'

const form = useForm({
    name: '',
    duplicate_of: null as number | null,
})

const source = computed({
    get: () => (form.duplicate_of === null ? EVERYTHING : String(form.duplicate_of)),
    set: (value) => (form.duplicate_of = value === EVERYTHING ? null : Number(value)),
})

const pagesLabel = (pages: number) => `${pages} ${pages === 1 ? 'page' : 'pages'}`

const create = () => {
    form.post(resumeRoutes.store.url(), { preserveScroll: true })
}
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-xl font-bold tracking-tight">Resumes</h1>
            <p class="text-sm text-muted-foreground">
                Each resume is drawn from your portfolio. One is public; the rest are yours to tailor and send.
            </p>
        </div>

        <div class="grid gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>New Resume</CardTitle>
                    <CardDescription
                        >A resume picks which parts of your portfolio to show, in what order, and can reword a few of them.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end" @submit.prevent="create">
                        <div class="grid gap-2">
                            <Label for="resume-name">Name</Label>
                            <Input id="resume-name" v-model="form.name" placeholder="Backend, One Page" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="resume-source">Start From</Label>
                            <Select v-model="source">
                                <SelectTrigger id="resume-source" class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="EVERYTHING">Everything in the Portfolio</SelectItem>
                                    <SelectItem v-for="resume in activeResumes" :key="resume.id" :value="String(resume.id)"
                                        >A Copy of {{ resume.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.duplicate_of" />
                        </div>
                        <Button type="submit" :disabled="form.processing">Create</Button>
                    </form>
                </CardContent>
            </Card>

            <div class="grid gap-3">
                <Link
                    v-for="resume in activeResumes"
                    :key="resume.id"
                    :href="resumeRoutes.edit.url(resume.id)"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card p-4 transition-colors hover:border-primary/50"
                >
                    <span class="grid gap-1">
                        <span class="flex flex-wrap items-center gap-2 font-medium">
                            {{ resume.name }}
                            <Badge v-if="resume.isPublic">Public</Badge>
                            <Badge v-if="resume.tailoredFor" variant="secondary">For {{ resume.tailoredFor }}</Badge>
                        </span>
                        <span class="text-sm text-muted-foreground">
                            {{ resume.template }} ·
                            <template v-if="resume.pageCount !== null">{{ pagesLabel(resume.pageCount) }}</template>
                            <template v-else>not built yet</template>
                            <template v-if="resume.builtAt"> · built {{ formatDate(resume.builtAt) }}</template>
                        </span>
                    </span>
                    <span class="text-sm text-muted-foreground">Edit</span>
                </Link>
            </div>

            <div v-if="archivedResumes.length" class="grid gap-3">
                <h2 class="text-sm font-medium text-muted-foreground">Archived</h2>
                <Link
                    v-for="resume in archivedResumes"
                    :key="resume.id"
                    :href="resumeRoutes.edit.url(resume.id)"
                    class="flex items-center justify-between gap-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground hover:text-foreground"
                >
                    <span
                        >{{ resume.name }}<template v-if="resume.tailoredFor"> · for {{ resume.tailoredFor }}</template></span
                    >
                    <span>Kept for the PDFs it sent</span>
                </Link>
            </div>
        </div>
    </div>
</template>
