<script setup lang="ts">
import { Button } from '@/components/ui/button'
import type { AssistantCoverLetter } from '@/types/assistant'
import { Check, ChevronDown, Copy, Download, FileText } from '@lucide/vue'
import { ref } from 'vue'

const props = defineProps<{
    letter: AssistantCoverLetter
}>()

const isOpen = ref(false)
const isCopied = ref(false)

const copy = async () => {
    await navigator.clipboard.writeText(props.letter.body)
    isCopied.value = true
    setTimeout(() => (isCopied.value = false), 2000)
}
</script>

<template>
    <div class="w-full max-w-[85%] rounded-xl border bg-background text-sm">
        <button type="button" class="flex w-full items-center gap-3 px-4 py-2.5 text-left" :aria-expanded="isOpen" @click="isOpen = !isOpen">
            <FileText class="size-4 shrink-0 text-muted-foreground" />
            <span class="min-w-0 flex-1">
                <span class="block truncate font-medium">{{ letter.title }}</span>
                <span class="block text-xs text-muted-foreground">Cover letter</span>
            </span>
            <ChevronDown :class="['size-4 shrink-0 text-muted-foreground transition-transform', isOpen && 'rotate-180']" />
        </button>

        <div v-if="isOpen" class="border-t px-4 py-3 leading-relaxed whitespace-pre-wrap">
            <p v-if="letter.recipient" class="mb-3 text-muted-foreground">
                {{ letter.recipient }}
            </p>
            {{ letter.body }}
        </div>

        <div class="flex justify-end gap-2 border-t px-4 py-2">
            <Button variant="ghost" size="sm" @click="copy">
                <component :is="isCopied ? Check : Copy" />
                {{ isCopied ? 'Copied' : 'Copy Text' }}
            </Button>
            <Button as-child size="sm">
                <a :href="letter.download_url" download>
                    <Download />
                    Download PDF
                </a>
            </Button>
        </div>
    </div>
</template>
