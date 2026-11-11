<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import atsCheckRoutes from '@/routes/ats-checks'
import type { AtsResumeChoice } from '@/types/ats'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{ canMatchJob: boolean; resumes: AtsResumeChoice[] }>()

const open = defineModel<boolean>('open', { required: true })

const form = useForm({ job_post: '', resume_id: props.resumes.find(({ isPublic }) => isPublic)?.id ?? null })

const run = () => {
    form.post(atsCheckRoutes.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false
            form.reset()
        },
    })
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <form class="grid gap-6" @submit.prevent="run">
                <DialogHeader>
                    <DialogTitle>Run ATS Check</DialogTitle>
                    <DialogDescription>
                        Scores your resume on what applicant tracking systems and recruiters look for. Paste a job post to also see how well your
                        resume matches its keywords.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="resumes.length > 1" class="grid gap-2">
                    <Label for="ats-resume">Resume</Label>
                    <select
                        id="ats-resume"
                        v-model="form.resume_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option v-for="resume in resumes" :key="resume.id" :value="resume.id">
                            {{ resume.name }}{{ resume.isPublic ? ' (public)' : '' }}
                        </option>
                    </select>
                    <InputError :message="form.errors.resume_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="ats-job-post">Job Post (Optional)</Label>
                    <Textarea
                        id="ats-job-post"
                        v-model="form.job_post"
                        rows="8"
                        :disabled="!canMatchJob"
                        :placeholder="canMatchJob ? 'Paste the full job description here.' : 'Add an AI API key under Settings to match a job post.'"
                        class="max-h-72"
                    />
                    <InputError :message="form.errors.job_post" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Checking…' : form.job_post.trim() ? 'Check Against Job' : 'Check Resume' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
