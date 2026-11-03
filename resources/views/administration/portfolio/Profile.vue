<script setup lang="ts">
import FieldInput from '@/components/administration/portfolio/FieldInput.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import PortfolioLayout from '@/layouts/AdministrationPortfolioLayout.vue'
import { fieldError, toFormValues, toPayload } from '@/lib/portfolioForm'
import administrationPortfolio from '@/routes/administration/portfolio'
import type { PortfolioField } from '@/types/portfolio'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
    profile: Record<string, unknown> | null
    fields: PortfolioField[]
}>()

const form = useForm(toFormValues(props.fields, props.profile))

const groups = computed(() =>
    [...new Set(props.fields.map(({ group }) => group))].map((name) => ({
        name,
        fields: props.fields.filter(({ group }) => group === name),
    })),
)

const submit = () => {
    form.transform((data) => toPayload(props.fields, data)).put(administrationPortfolio.profile.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    })
}
</script>

<template>
    <PortfolioLayout>
        <form class="grid gap-6" @submit.prevent="submit">
            <Card v-for="group in groups" :key="group.name ?? ''">
                <CardHeader>
                    <CardTitle>{{ group.name }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-6">
                    <FieldInput
                        v-for="field in group.fields"
                        :key="field.name"
                        v-model="form[field.name]"
                        :field="field"
                        :error="fieldError(form.errors, field.name)"
                    />
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save profile</Button>
            </div>
        </form>
    </PortfolioLayout>
</template>
