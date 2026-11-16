<script setup lang="ts">
import AskAssistantButton from '@/components/app/assistant/AskAssistantButton.vue'
import FloatingAssistant from '@/components/app/assistant/FloatingAssistant.vue'
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useFormProposals } from '@/composables/useFormProposals'
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { toFormValues } from '@/lib/portfolioForm'
import portfolioRoutes from '@/routes/portfolio'
import type { AssistantConversation, AssistantConversationSummary, AssistantProposal } from '@/types/assistant'
import type { PortfolioField } from '@/types/portfolio'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const props = defineProps<{
    copy: Record<string, unknown>
    fields: PortfolioField[]
    assistant: { isReady: boolean; provider: string; model: string; conversations: Omit<AssistantConversationSummary, 'job'>[] }
    assistantConversation?: AssistantConversation | null
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

const pageField = (page: string, attribute: string): string => `page_${page}_${attribute}`
const sectionField = (section: string, attribute: string): string => `section_${section}_${attribute}`

const inputIds: Record<string, string> = Object.fromEntries([
    ...Object.keys(pageNames).flatMap((key) => [
        [pageField(key, 'title'), `page-${key}-title`],
        [pageField(key, 'description'), `page-${key}-description`],
        [pageField(key, 'image_alt'), `page-${key}-alt`],
    ]),
    ...Object.keys(sectionNames).flatMap((key) => [
        [sectionField(key, 'eyebrow'), `section-${key}-eyebrow`],
        [sectionField(key, 'title'), `section-${key}-title`],
        [sectionField(key, 'description'), `section-${key}-description`],
    ]),
    ...Object.keys(workLabels).map((label) => [sectionField('work', label), `section-work-${label}`]),
])

const form = useForm(toFormValues(props.fields, props.copy) as Record<string, string>)

const errors = form.errors as Record<string, string | undefined>

const { isConfirmingLeave, leave } = useUnsavedChangesGuard(() => form.isDirty)

const assistantWindow = ref<InstanceType<typeof FloatingAssistant> | null>(null)
const proposals = useFormProposals(form, () => props.fields)

const assistantContext = () => ({ portfolio_draft: { section: 'copy', item: null, values: form.data() } })

const isForCopy = (proposal: AssistantProposal): boolean => proposal.target === 'portfolio:copy'

const loadProposal = async (proposal: AssistantProposal) => {
    const [first] = proposals.load(proposal)

    await nextTick()

    if (first) {
        document.getElementById(inputIds[first.name] ?? '')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
}

const labelOf = (name: string): string => props.fields.find((field) => field.name === name)?.label ?? name

const askAbout = (name: string) => assistantWindow.value?.ask(`${labelOf(name)}: `)

const nullable = (value: string | undefined): string => value ?? ''

const submit = () => {
    form.transform((data) => ({
        assistant_proposal_ids: proposals.loadedProposalIds.value,
        pages: Object.fromEntries(
            Object.keys(pageNames).map((key) => [
                key,
                {
                    title: nullable(data[pageField(key, 'title')]),
                    description: nullable(data[pageField(key, 'description')]),
                    image_alt: nullable(data[pageField(key, 'image_alt')]),
                },
            ]),
        ),
        sections: Object.fromEntries(
            Object.keys(sectionNames).map((key) => [
                key,
                {
                    eyebrow: nullable(data[sectionField(key, 'eyebrow')]),
                    title: nullable(data[sectionField(key, 'title')]),
                    description: nullable(data[sectionField(key, 'description')]),
                    labels:
                        key === 'work'
                            ? Object.fromEntries(Object.keys(workLabels).map((label) => [label, nullable(data[sectionField(key, label)])]))
                            : {},
                },
            ]),
        ),
    })).put(portfolioRoutes.copy.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults()
            proposals.forget()
        },
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
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`page-${key}-title`">Title *</Label>
                            <AskAssistantButton :label="labelOf(pageField(key, 'title'))" @ask="askAbout(pageField(key, 'title'))" />
                        </div>
                        <Input :id="`page-${key}-title`" v-model="form[pageField(key, 'title')]" required />
                        <InputError :message="errors[`pages.${key}.title`]" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`page-${key}-description`">Description</Label>
                            <AskAssistantButton :label="labelOf(pageField(key, 'description'))" @ask="askAbout(pageField(key, 'description'))" />
                        </div>
                        <Textarea :id="`page-${key}-description`" v-model="form[pageField(key, 'description')]" rows="2" />
                        <InputError :message="errors[`pages.${key}.description`]" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`page-${key}-alt`">Share Image Description</Label>
                            <AskAssistantButton :label="labelOf(pageField(key, 'image_alt'))" @ask="askAbout(pageField(key, 'image_alt'))" />
                        </div>
                        <Input :id="`page-${key}-alt`" v-model="form[pageField(key, 'image_alt')]" />
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
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`section-${key}-eyebrow`">Small Heading</Label>
                            <AskAssistantButton :label="labelOf(sectionField(key, 'eyebrow'))" @ask="askAbout(sectionField(key, 'eyebrow'))" />
                        </div>
                        <Input :id="`section-${key}-eyebrow`" v-model="form[sectionField(key, 'eyebrow')]" />
                        <InputError :message="errors[`sections.${key}.eyebrow`]" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`section-${key}-title`">Heading *</Label>
                            <AskAssistantButton :label="labelOf(sectionField(key, 'title'))" @ask="askAbout(sectionField(key, 'title'))" />
                        </div>
                        <Input :id="`section-${key}-title`" v-model="form[sectionField(key, 'title')]" required />
                        <InputError :message="errors[`sections.${key}.title`]" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label :for="`section-${key}-description`">Description</Label>
                            <AskAssistantButton
                                :label="labelOf(sectionField(key, 'description'))"
                                @ask="askAbout(sectionField(key, 'description'))"
                            />
                        </div>
                        <Textarea :id="`section-${key}-description`" v-model="form[sectionField(key, 'description')]" rows="2" />
                        <InputError :message="errors[`sections.${key}.description`]" />
                    </div>
                    <template v-if="key === 'work'">
                        <div v-for="(label, labelKey) in workLabels" :key="labelKey" class="grid gap-2">
                            <div class="flex items-center justify-between gap-2">
                                <Label :for="`section-work-${labelKey}`">{{ label }}</Label>
                                <AskAssistantButton
                                    :label="labelOf(sectionField('work', labelKey))"
                                    @ask="askAbout(sectionField('work', labelKey))"
                                />
                            </div>
                            <Input :id="`section-work-${labelKey}`" v-model="form[sectionField('work', labelKey)]" />
                            <InputError :message="errors[`sections.work.labels.${labelKey}`]" />
                        </div>
                    </template>
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Changes</Button>
            </div>
        </form>

        <FloatingAssistant
            ref="assistantWindow"
            :assistant="assistant"
            :conversation="assistantConversation"
            subject="your headings and SEO"
            intro="Ask about your page titles, sharing text or section headings, or use the sparkle beside a field. Suggestions load into the form, and nothing is saved until you press Save Changes."
            :context="assistantContext"
            :loads-into-editor="isForCopy"
            :loaded-proposal-ids="proposals.loadedProposalIds.value"
            @load="loadProposal"
            @unload="proposals.unload"
        />

        <ConfirmDialog
            v-model:open="isConfirmingLeave"
            title="Leave Without Saving?"
            description="Your unsaved changes will be lost."
            confirm-label="Leave"
            @confirm="leave"
        />
    </PortfolioLayout>
</template>
