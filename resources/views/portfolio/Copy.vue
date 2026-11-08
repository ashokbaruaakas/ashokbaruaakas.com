<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import portfolioRoutes from '@/routes/portfolio'
import { useForm } from '@inertiajs/vue3'

type SectionRecord = { eyebrow: string | null; title: string; description: string | null; labels: Record<string, string> | null }
type PageRecord = { title: string; description: string | null; image_alt: string | null }

const props = defineProps<{
    sections: Partial<Record<string, SectionRecord>>
    pages: Partial<Record<string, PageRecord>>
}>()

const sectionNames: Record<string, string> = {
    about: 'About',
    skills: 'Skills',
    work: 'Work',
    'case-studies': 'Case Studies',
    experience: 'Experience',
    education: 'Education',
    testimonials: 'Testimonials',
    connect: 'Connect',
    'open-source': 'Open Source',
}

const pageNames: Record<string, string> = { home: 'Home', resume: 'Resume', 'open-source': 'Open Source' }

const workLabels: Record<string, string> = {
    flagship: 'Flagship Heading',
    caseStudiesLink: 'Case Studies Link',
    supporting: 'Supporting Heading',
    supportingDescription: 'Supporting Description',
}

const form = useForm({
    sections: Object.fromEntries(
        Object.entries(sectionNames).map(([key, name]) => {
            const section = props.sections[key]

            return [
                key,
                {
                    eyebrow: section?.eyebrow ?? '',
                    title: section?.title ?? name,
                    description: section?.description ?? '',
                    labels: key === 'work' ? Object.fromEntries(Object.keys(workLabels).map((label) => [label, section?.labels?.[label] ?? ''])) : {},
                },
            ]
        }),
    ),
    pages: Object.fromEntries(
        Object.entries(pageNames).map(([key, name]) => {
            const page = props.pages[key]

            return [key, { title: page?.title ?? name, description: page?.description ?? '', image_alt: page?.image_alt ?? '' }]
        }),
    ),
})

const errors = form.errors as Record<string, string | undefined>

const submit = () => {
    form.put(portfolioRoutes.copy.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    })
}
</script>

<template>
    <PortfolioLayout>
        <form class="grid gap-6" @submit.prevent="submit">
            <h2 class="text-lg font-semibold">Page Titles and Sharing</h2>
            <Card v-for="(name, key) in pageNames" :key="key">
                <CardHeader>
                    <CardTitle>{{ name }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label :for="`page-${key}-title`">Title *</Label>
                        <Input :id="`page-${key}-title`" v-model="form.pages[key]!.title" required />
                        <InputError :message="errors[`pages.${key}.title`]" />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`page-${key}-description`">Description</Label>
                        <Textarea :id="`page-${key}-description`" v-model="form.pages[key]!.description" rows="2" />
                        <InputError :message="errors[`pages.${key}.description`]" />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`page-${key}-alt`">Share Image Description</Label>
                        <Input :id="`page-${key}-alt`" v-model="form.pages[key]!.image_alt" />
                        <InputError :message="errors[`pages.${key}.image_alt`]" />
                    </div>
                </CardContent>
            </Card>

            <h2 class="text-lg font-semibold">Section Headings</h2>
            <Card v-for="(name, key) in sectionNames" :key="key">
                <CardHeader>
                    <CardTitle>{{ name }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label :for="`section-${key}-eyebrow`">Small Heading</Label>
                        <Input :id="`section-${key}-eyebrow`" v-model="form.sections[key]!.eyebrow" />
                        <InputError :message="errors[`sections.${key}.eyebrow`]" />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`section-${key}-title`">Heading *</Label>
                        <Input :id="`section-${key}-title`" v-model="form.sections[key]!.title" required />
                        <InputError :message="errors[`sections.${key}.title`]" />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`section-${key}-description`">Description</Label>
                        <Textarea :id="`section-${key}-description`" v-model="form.sections[key]!.description" rows="2" />
                        <InputError :message="errors[`sections.${key}.description`]" />
                    </div>
                    <template v-if="key === 'work'">
                        <div v-for="(label, labelKey) in workLabels" :key="labelKey" class="grid gap-2">
                            <Label :for="`section-work-${labelKey}`">{{ label }}</Label>
                            <Input :id="`section-work-${labelKey}`" v-model="form.sections.work!.labels[labelKey]" />
                            <InputError :message="errors[`sections.work.labels.${labelKey}`]" />
                        </div>
                    </template>
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>
    </PortfolioLayout>
</template>
