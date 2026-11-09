<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import atsCheckRoutes from '@/routes/ats-checks'
import { useForm } from '@inertiajs/vue3'

defineProps<{ canMatchJob: boolean }>()

const open = defineModel<boolean>('open', { required: true })

const form = useForm({ job_post: '' })

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
