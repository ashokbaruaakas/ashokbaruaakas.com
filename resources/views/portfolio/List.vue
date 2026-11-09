<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import FieldInput from '@/components/app/portfolio/FieldInput.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card'
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard'
import PortfolioLayout from '@/layouts/PortfolioEditorLayout.vue'
import { fieldError, toFormValues, toPayload } from '@/lib/portfolioForm'
import portfolioRoutes from '@/routes/portfolio'
import type { PortfolioField, PortfolioRecord } from '@/types/portfolio'
import { router, useForm } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue'
import { computed, ref } from 'vue'

const props = defineProps<{
    list: { key: string; title: string; description: string; titleField: string; subtitleField: string; canBeHidden: boolean }
    fields: PortfolioField[]
    items: PortfolioRecord[]
}>()

const isEditorOpen = ref(false)
const editingItem = ref<PortfolioRecord | null>(null)
const removingItem = ref<PortfolioRecord | null>(null)
const isRemoving = ref(false)

const form = useForm(toFormValues(props.fields, null))

const { isConfirmingLeave, leave } = useUnsavedChangesGuard(() => isEditorOpen.value && form.isDirty)

const textOf = (value: unknown): string => (Array.isArray(value) ? value.join(', ') : String(value ?? ''))

const openEditor = (item: PortfolioRecord | null) => {
    editingItem.value = item
    form.clearErrors()
    form.defaults(toFormValues(props.fields, item))
    form.reset()
    isEditorOpen.value = true
}

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (isEditorOpen.value = false) }
    const request = form.transform((data) => toPayload(props.fields, data))

    if (editingItem.value) {
        request.put(portfolioRoutes.lists.update.url({ list: props.list.key, item: editingItem.value.id }), options)
    } else {
        request.post(portfolioRoutes.lists.store.url(props.list.key), options)
    }
}

const move = (index: number, offset: number) => {
    const ids = props.items.map(({ id }) => id)
    const [id] = ids.splice(index, 1)

    if (id === undefined) {
        return
    }

    ids.splice(index + offset, 0, id)
    router.put(portfolioRoutes.lists.order.update.url(props.list.key), { ids }, { preserveScroll: true })
}

const toggleVisibility = (item: PortfolioRecord) => {
    const isVisible = !item.is_visible

    router
        .optimistic<{ items: PortfolioRecord[] }>((pageProps) => ({
            items: pageProps.items.map((other) => (other.id === item.id ? { ...other, is_visible: isVisible } : other)),
        }))
        .put(
            portfolioRoutes.lists.visibility.update.url({ list: props.list.key, item: item.id }),
            { is_visible: isVisible },
            { preserveScroll: true },
        )
}

const isConfirmingRemoval = computed({
    get: () => removingItem.value !== null,
    set: (open) => {
        if (!open) {
            removingItem.value = null
        }
    },
})

const remove = () => {
    if (!removingItem.value) {
        return
    }

    router.delete(portfolioRoutes.lists.destroy.url({ list: props.list.key, item: removingItem.value.id }), {
        preserveScroll: true,
        onStart: () => (isRemoving.value = true),
        onFinish: () => {
            isRemoving.value = false
            removingItem.value = null
        },
    })
}
</script>

<template>
    <PortfolioLayout>
        <Card>
            <CardHeader class="flex flex-row items-start justify-between gap-4">
                <CardDescription>{{ list.description }}</CardDescription>
                <Button type="button" size="sm" @click="openEditor(null)"><Plus /> Add</Button>
            </CardHeader>
            <CardContent>
                <p v-if="items.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    Nothing here yet. This section stays hidden on your site until you add something.
                </p>
                <ol v-else class="divide-y divide-border">
                    <li v-for="(item, index) in items" :key="item.id" class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium" :class="{ 'text-muted-foreground': list.canBeHidden && !item.is_visible }">
                                    {{ textOf(item[list.titleField]) }}
                                </p>
                                <Badge v-if="list.canBeHidden && !item.is_visible" variant="outline" class="shrink-0">Hidden</Badge>
                            </div>
                            <p class="truncate text-sm text-muted-foreground">{{ textOf(item[list.subtitleField]) }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                v-if="list.canBeHidden"
                                type="button"
                                variant="ghost"
                                size="icon"
                                :aria-label="`${item.is_visible ? 'Hide' : 'Show'} ${textOf(item[list.titleField])}`"
                                @click="toggleVisibility(item)"
                            >
                                <Eye v-if="item.is_visible" />
                                <EyeOff v-else />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === 0"
                                :aria-label="`Move ${textOf(item[list.titleField])} up`"
                                @click="move(index, -1)"
                            >
                                <ArrowUp />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === items.length - 1"
                                :aria-label="`Move ${textOf(item[list.titleField])} down`"
                                @click="move(index, 1)"
                            >
                                <ArrowDown />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :aria-label="`Edit ${textOf(item[list.titleField])}`"
                                @click="openEditor(item)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :aria-label="`Remove ${textOf(item[list.titleField])}`"
                                @click="removingItem = item"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Sheet v-model:open="isEditorOpen">
            <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
                <form class="flex min-h-full flex-col" @submit.prevent="submit">
                    <SheetHeader>
                        <SheetTitle>{{
                            editingItem ? `Edit ${textOf(editingItem[list.titleField])}` : `Add to ${list.title.toLowerCase()}`
                        }}</SheetTitle>
                        <SheetDescription>{{ list.description }}</SheetDescription>
                    </SheetHeader>
                    <div class="grid flex-1 content-start gap-6 px-4 py-2">
                        <FieldInput
                            v-for="field in fields"
                            :key="field.name"
                            v-model="form[field.name]"
                            :field="field"
                            :error="fieldError(form.errors, field.name)"
                            :mention="editingItem ? `portfolio:${list.key}/${editingItem.id}` : undefined"
                        />
                    </div>
                    <SheetFooter>
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>

        <ConfirmDialog
            v-model:open="isConfirmingRemoval"
            :title="`Remove ${textOf(removingItem?.[list.titleField])}?`"
            description="This can't be undone."
            confirm-label="Remove"
            :processing="isRemoving"
            @confirm="remove"
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
