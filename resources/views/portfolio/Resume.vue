<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { resume as resumePage } from '@/routes'
import portfolioRoutes from '@/routes/portfolio'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type ResumeTemplateOption = { value: string; label: string; description: string; isAtsSafe: boolean }

const props = defineProps<{
    resume: { template: string; pageLimit: number; pageCount: number | null; isOverPageLimit: boolean }
    templates: ResumeTemplateOption[]
}>()

const form = useForm({
    template: props.resume.template,
    page_limit: props.resume.pageLimit,
})

const pageCountLabel = computed(() => (props.resume.pageCount === 1 ? '1 page' : `${props.resume.pageCount} pages`))

const submit = () => {
    form.put(portfolioRoutes.resume.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    })
}
</script>

<template>
    <PortfolioLayout>
        <form class="grid gap-6" @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>Length</CardTitle>
                    <CardDescription>
                        <template v-if="resume.pageCount === null">The page count shows after the resume PDF is built.</template>
                        <template v-else>
                            The latest resume PDF is <span class="font-medium text-foreground">{{ pageCountLabel }}</span
                            ><template v-if="resume.isOverPageLimit">, over your limit</template>.
                        </template>
                        <a :href="resumePage.url()" target="_blank" class="ml-1 underline underline-offset-4">View Resume</a>
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <Label for="page-limit">Page Limit</Label>
                    <Input id="page-limit" v-model.number="form.page_limit" type="number" min="1" max="5" class="w-24" required />
                    <p class="text-sm text-muted-foreground">The dashboard and the assistant warn you when the PDF runs longer.</p>
                    <InputError :message="form.errors.page_limit" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Template</CardTitle>
                    <CardDescription>The layout of the resume page and its PDF.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3">
                    <label
                        v-for="template in templates"
                        :key="template.value"
                        class="flex cursor-pointer items-start gap-3 rounded-md border p-4 has-[:checked]:border-primary"
                    >
                        <input v-model="form.template" type="radio" name="template" :value="template.value" class="mt-1 accent-primary" />
                        <span class="grid gap-1">
                            <span class="flex items-center gap-2 font-medium">
                                {{ template.label }}
                                <Badge v-if="template.isAtsSafe" variant="secondary">ATS-Safe</Badge>
                            </span>
                            <span class="text-sm text-muted-foreground">{{ template.description }}</span>
                        </span>
                    </label>
                    <InputError :message="form.errors.template" />
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>
    </PortfolioLayout>
</template>
