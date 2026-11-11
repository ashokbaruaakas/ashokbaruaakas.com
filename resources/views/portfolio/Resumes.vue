<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { formatDate } from '@/lib/helpers'
import portfolioRoutes from '@/routes/portfolio'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type ResumeSummary = {
    id: number
    name: string
    template: string
    pageCount: number | null
    pageLimit: number
    isOverPageLimit: boolean
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

const form = useForm({
    name: '',
    duplicate_of: null as number | null,
})

const pagesLabel = (pages: number) => `${pages} ${pages === 1 ? 'page' : 'pages'}`

const create = () => {
    form.post(portfolioRoutes.resumes.store.url(), { preserveScroll: true })
}
</script>

<template>
    <PortfolioLayout>
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
                            <select
                                id="resume-source"
                                v-model="form.duplicate_of"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option :value="null">Everything in the portfolio</option>
                                <option v-for="resume in activeResumes" :key="resume.id" :value="resume.id">A copy of {{ resume.name }}</option>
                            </select>
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
                    :href="portfolioRoutes.resumes.edit.url(resume.id)"
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
                            <template v-if="resume.pageCount !== null">
                                <span :class="{ 'font-medium text-amber-700 dark:text-amber-400': resume.isOverPageLimit }">{{
                                    pagesLabel(resume.pageCount)
                                }}</span>
                                of {{ resume.pageLimit }}
                            </template>
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
                    :href="portfolioRoutes.resumes.edit.url(resume.id)"
                    class="flex items-center justify-between gap-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground hover:text-foreground"
                >
                    <span
                        >{{ resume.name }}<template v-if="resume.tailoredFor"> · for {{ resume.tailoredFor }}</template></span
                    >
                    <span>Kept for the PDFs it sent</span>
                </Link>
            </div>
        </div>
    </PortfolioLayout>
</template>
