<script setup lang="ts">
import type { Alignment, Block } from '@/components/app/assistant/markdown'
import MarkdownInline from '@/components/app/assistant/MarkdownInline.vue'
import type { AssistantJob } from '@/types/assistant'

defineProps<{
    blocks: Block[]
    jobs: AssistantJob[]
    compact?: boolean
}>()

const headingClasses: Record<number, string> = {
    1: 'text-base font-semibold',
    2: 'text-base font-semibold',
    3: 'font-semibold',
}

const alignClasses: Record<NonNullable<Alignment>, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
}

const alignClass = (align: Alignment): string => alignClasses[align ?? 'left']
</script>

<template>
    <div :class="compact ? 'space-y-1' : 'space-y-3'">
        <template v-for="(block, index) in blocks" :key="index">
            <p v-if="block.type === 'paragraph'"><MarkdownInline :nodes="block.content" :jobs="jobs" /></p>
            <component
                :is="`h${Math.min(block.level + 2, 6)}`"
                v-else-if="block.type === 'heading'"
                :class="headingClasses[block.level] ?? 'font-semibold'"
            >
                <MarkdownInline :nodes="block.content" :jobs="jobs" />
            </component>
            <pre
                v-else-if="block.type === 'code'"
                class="overflow-x-auto rounded-lg bg-background p-3 font-mono text-xs leading-relaxed"
            ><code>{{ block.text }}</code></pre>
            <blockquote v-else-if="block.type === 'quote'" class="border-l-2 border-border pl-3 text-muted-foreground">
                <MarkdownBlocks :blocks="block.blocks" :jobs="jobs" />
            </blockquote>
            <component
                :is="block.ordered ? 'ol' : 'ul'"
                v-else-if="block.type === 'list'"
                :start="block.ordered ? block.start : undefined"
                :class="['space-y-1 pl-5', block.ordered ? 'list-decimal' : 'list-disc']"
            >
                <li v-for="(item, itemIndex) in block.items" :key="itemIndex" class="pl-1">
                    <MarkdownBlocks :blocks="item" :jobs="jobs" compact />
                </li>
            </component>
            <div v-else-if="block.type === 'table'" class="overflow-x-auto">
                <table class="w-full border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-border">
                            <th
                                v-for="(cell, cellIndex) in block.header"
                                :key="cellIndex"
                                :class="['px-2 py-1.5 font-semibold', alignClass(block.align[cellIndex] ?? null)]"
                            >
                                <MarkdownInline :nodes="cell" :jobs="jobs" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, rowIndex) in block.rows" :key="rowIndex" class="border-b border-border/60 last:border-0">
                            <td
                                v-for="(cell, cellIndex) in row"
                                :key="cellIndex"
                                :class="['px-2 py-1.5 align-top', alignClass(block.align[cellIndex] ?? null)]"
                            >
                                <MarkdownInline :nodes="cell" :jobs="jobs" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <hr v-else-if="block.type === 'rule'" class="border-border" />
        </template>
    </div>
</template>
