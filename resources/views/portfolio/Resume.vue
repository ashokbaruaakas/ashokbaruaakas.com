<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import ResumeTemplatePreview from '@/components/app/portfolio/ResumeTemplatePreview.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { resume as resumePage } from '@/routes'
import portfolioRoutes from '@/routes/portfolio'
import { TEMPLATE_ESTIMATE_MESSAGE } from '@/views/resume/templates'
import { router, useForm } from '@inertiajs/vue3'
import { useEventListener } from '@vueuse/core'
import { computed, reactive } from 'vue'

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

const estimatedPages = reactive<Record<string, number>>({})

useEventListener(window, 'message', (event: MessageEvent) => {
    if (event.origin === window.location.origin && event.data?.type === TEMPLATE_ESTIMATE_MESSAGE) {
        estimatedPages[event.data.template] = event.data.pages
    }
})

const estimateLabel = (template: string): string => {
    const pages = estimatedPages[template]

    if (pages === undefined) {
        return 'Measuring…'
    }

    return `About ${pages} ${pages === 1 ? 'page' : 'pages'}`
}

const chooseTemplate = (template: string) => {
    if (template === props.resume.template) {
        return
    }

    form.template = template
    router.put(
        portfolioRoutes.resume.update.url(),
        { template, page_limit: props.resume.pageLimit },
        {
            preserveScroll: true,
            onSuccess: () => form.defaults('template', template),
            onError: () => (form.template = props.resume.template),
        },
    )
}

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
                    <CardDescription>The layout of the resume page and its PDF. Choosing one rebuilds the PDF right away.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <label
                        v-for="template in templates"
                        :key="template.value"
                        class="grid cursor-pointer gap-3 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                    >
                        <ResumeTemplatePreview
                            :src="portfolioRoutes.resume.preview.url(template.value)"
                            :title="`${template.label} template preview`"
                        />
                        <span class="flex items-start gap-2">
                            <input
                                type="radio"
                                name="template"
                                :value="template.value"
                                :checked="form.template === template.value"
                                class="mt-1 accent-primary"
                                :disabled="form.processing"
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
                    <InputError :message="form.errors.template" />
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>
    </PortfolioLayout>
</template>
