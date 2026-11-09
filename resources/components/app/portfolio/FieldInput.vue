<script setup lang="ts">
import LinkRow from '@/components/app/portfolio/LinkRow.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import assistantRoutes from '@/routes/assistant'
import type { PortfolioField, PortfolioFieldValue, PortfolioLinkValue } from '@/types/portfolio'
import { Link } from '@inertiajs/vue3'
import { Plus, Sparkles } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps<{
    field: PortfolioField
    modelValue: PortfolioFieldValue
    error?: string
    mention?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: PortfolioFieldValue]
}>()

const id = computed(() => `field-${props.field.name}`)
const helpId = computed(() => (props.field.help ? `${id.value}-help` : undefined))
const textValue = computed(() => String(props.modelValue ?? ''))
const assistantHref = computed(() =>
    props.mention && props.field.type !== 'links'
        ? assistantRoutes.show.url(undefined, { query: { mention: props.mention, field: props.field.label } })
        : null,
)
const links = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

const updateLink = (index: number, changes: Partial<PortfolioLinkValue>) => {
    emit(
        'update:modelValue',
        links.value.map((link, linkIndex) => (linkIndex === index ? { ...link, ...changes } : link)),
    )
}

const addLink = () => emit('update:modelValue', [...links.value, { label: '', url: '', is_primary: false }])

const removeLink = (index: number) =>
    emit(
        'update:modelValue',
        links.value.filter((_, linkIndex) => linkIndex !== index),
    )
</script>

<template>
    <div v-if="field.type === 'boolean'" class="flex items-start gap-3">
        <Checkbox
            :id="id"
            :model-value="Boolean(modelValue)"
            :aria-describedby="helpId"
            @update:model-value="emit('update:modelValue', Boolean($event))"
        />
        <div class="grid gap-1">
            <div class="flex items-center gap-1.5">
                <Label :for="id">{{ field.label }}</Label>
                <Button v-if="assistantHref" as-child variant="ghost" size="icon-xs" class="text-muted-foreground">
                    <Link
                        :href="assistantHref"
                        :aria-label="`Ask the Assistant About ${field.label}`"
                        :title="`Ask the Assistant About ${field.label}`"
                    >
                        <Sparkles />
                    </Link>
                </Button>
            </div>
            <p v-if="field.help" :id="helpId" class="text-sm text-muted-foreground">{{ field.help }}</p>
            <InputError :message="error" />
        </div>
    </div>

    <div v-else-if="field.type === 'links'" class="grid gap-3">
        <div class="flex items-center justify-between">
            <Label>{{ field.label }}</Label>
            <Button type="button" variant="outline" size="sm" @click="addLink"><Plus /> Add Link</Button>
        </div>
        <LinkRow
            v-for="(link, index) in links"
            :key="index"
            :link="link"
            :index="index"
            @update="(changes) => updateLink(index, changes)"
            @remove="removeLink(index)"
        />
        <InputError :message="error" />
    </div>

    <div v-else class="grid gap-2">
        <div class="flex items-center justify-between gap-2">
            <Label :for="id">{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></Label>
            <Button v-if="assistantHref" as-child variant="ghost" size="icon-xs" class="text-muted-foreground">
                <Link :href="assistantHref" :aria-label="`Ask the Assistant About ${field.label}`" :title="`Ask the Assistant About ${field.label}`">
                    <Sparkles />
                </Link>
            </Button>
        </div>

        <Textarea
            v-if="field.type === 'textarea' || field.type === 'lines'"
            :id="id"
            :model-value="textValue"
            :required="field.required && field.type === 'textarea'"
            :aria-describedby="helpId"
            :aria-invalid="Boolean(error)"
            :rows="field.type === 'lines' ? 5 : 4"
            @update:model-value="emit('update:modelValue', $event)"
        />

        <Select v-else-if="field.type === 'select'" :model-value="textValue" @update:model-value="emit('update:modelValue', String($event))">
            <SelectTrigger :id="id" class="w-full" :aria-describedby="helpId" :aria-invalid="Boolean(error)">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
            </SelectContent>
        </Select>

        <Input
            v-else
            :id="id"
            :model-value="field.type === 'number' ? Number(modelValue) : textValue"
            :type="field.type"
            :min="field.type === 'number' ? 0 : undefined"
            :required="field.required"
            :aria-describedby="helpId"
            :aria-invalid="Boolean(error)"
            @update:model-value="emit('update:modelValue', field.type === 'number' ? Number($event) : String($event))"
        />

        <p v-if="field.help" :id="helpId" class="text-sm text-muted-foreground">{{ field.help }}</p>
        <InputError :message="error" />
    </div>
</template>
