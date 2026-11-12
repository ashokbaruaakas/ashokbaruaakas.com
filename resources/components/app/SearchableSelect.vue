<script setup lang="ts">
import { Check, ChevronsUpDown, Search } from '@lucide/vue'
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui'
import { computed } from 'vue'

const props = defineProps<{
    id?: string
    options: { value: string; label: string }[]
    searchPlaceholder?: string
    emptyText?: string
}>()

const model = defineModel<string>({ required: true })

const selectedLabel = computed(() => props.options.find(({ value }) => value === model.value)?.label ?? '')
</script>

<template>
    <ComboboxRoot v-model="model" :reset-search-term-on-select="true" :reset-search-term-on-blur="true">
        <ComboboxAnchor as-child>
            <ComboboxTrigger
                :id="id"
                class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-left text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 dark:hover:bg-input/50"
            >
                <span class="truncate">{{ selectedLabel }}</span>
                <ChevronsUpDown class="size-4 shrink-0 opacity-50" aria-hidden="true" />
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                :side-offset="4"
                class="z-50 w-(--reka-combobox-trigger-width) min-w-[12rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            >
                <div class="flex items-center gap-2 border-b px-3">
                    <Search class="size-4 shrink-0 opacity-50" aria-hidden="true" />
                    <ComboboxInput
                        auto-focus
                        :placeholder="searchPlaceholder ?? 'Search…'"
                        class="h-9 w-full bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground"
                    />
                </div>
                <ComboboxViewport class="max-h-72 overflow-y-auto p-1">
                    <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">{{ emptyText ?? 'Nothing matches.' }}</ComboboxEmpty>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :text-value="option.label"
                        class="relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <ComboboxItemIndicator class="absolute right-2 flex size-3.5 items-center justify-center">
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
