<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import FieldInput from '@/components/app/portfolio/FieldInput.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { cn } from '@/lib/utils'
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { fieldError, toFormValues, toPayload } from '@/lib/portfolioForm'
import portfolioRoutes from '@/routes/portfolio'
import type { PortfolioField } from '@/types/portfolio'
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps<{
    profile: Record<string, unknown> | null
    fields: PortfolioField[]
}>()

const form = useForm(toFormValues(props.fields, props.profile))

const { isConfirmingLeave, leave } = useUnsavedChangesGuard(() => form.isDirty)

const groups = computed(() =>
    [...new Set(props.fields.map(({ group }) => group ?? ''))].map((name) => {
        const fields = props.fields.filter(({ group }) => (group ?? '') === name)

        return { name, fields, hasError: fields.some((field) => fieldError(form.errors, field.name)) }
    }),
)

const selectedGroupName = ref(groups.value[0]?.name)
const selectedGroup = computed(() => groups.value.find(({ name }) => name === selectedGroupName.value))

const submit = () => {
    form.transform((data) => toPayload(props.fields, data)).put(portfolioRoutes.profile.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
        onError: () => {
            selectedGroupName.value = groups.value.find(({ hasError }) => hasError)?.name ?? selectedGroupName.value
        },
    })
}
</script>

<template>
    <PortfolioLayout>
        <form class="grid gap-6" @submit.prevent="submit">
            <div class="flex flex-wrap gap-1 border-b">
                <button
                    v-for="group in groups"
                    :key="group.name"
                    type="button"
                    :aria-pressed="group.name === selectedGroupName"
                    :class="
                        cn(
                            '-mb-px inline-flex min-h-11 cursor-pointer items-center gap-1.5 border-b-2 px-3 text-sm font-medium transition-colors',
                            group.name === selectedGroupName
                                ? 'border-emerald-700 text-foreground dark:border-emerald-400'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="selectedGroupName = group.name"
                >
                    {{ group.name }}
                    <span v-if="group.hasError" class="size-1.5 rounded-full bg-destructive" aria-label="Has errors" />
                </button>
            </div>

            <Card v-if="selectedGroup">
                <CardContent class="grid gap-6">
                    <FieldInput
                        v-for="field in selectedGroup.fields"
                        :key="field.name"
                        v-model="form[field.name]"
                        :field="field"
                        :error="fieldError(form.errors, field.name)"
                        mention="portfolio:profile"
                    />
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save Profile</Button>
            </div>
        </form>

        <ConfirmDialog
            v-model:open="isConfirmingLeave"
            title="Leave Without Saving?"
            description="Your unsaved changes will be lost."
            confirm-label="Leave"
            @confirm="leave"
        />
    </PortfolioLayout>
</template>
