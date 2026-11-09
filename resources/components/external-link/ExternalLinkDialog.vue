<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { ExternalLink } from '@lucide/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { externalUrlFromClick } from './externalLink'

const pendingUrl = ref<URL | null>(null)

const isOpen = computed({
    get: () => pendingUrl.value !== null,
    set: (open) => {
        if (!open) {
            pendingUrl.value = null
        }
    },
})

const interceptClick = (event: MouseEvent) => {
    const url = externalUrlFromClick(event, window.location.origin)

    if (url !== null) {
        event.preventDefault()
        pendingUrl.value = url
    }
}

const openInNewTab = () => {
    if (pendingUrl.value !== null) {
        window.open(pendingUrl.value.href, '_blank', 'noopener,noreferrer')
    }

    pendingUrl.value = null
}

const continueToSite = () => {
    if (pendingUrl.value !== null) {
        window.location.assign(pendingUrl.value.href)
    }
}

onMounted(() => document.addEventListener('click', interceptClick, { capture: true }))
onUnmounted(() => document.removeEventListener('click', interceptClick, { capture: true }))
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>You're Leaving This Site</DialogTitle>
                <DialogDescription>This link goes to {{ pendingUrl?.hostname }}, a site this one doesn't control.</DialogDescription>
            </DialogHeader>

            <p class="flex items-start gap-2 rounded-md bg-muted px-3 py-2 font-mono text-xs break-all text-muted-foreground">
                <ExternalLink class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                {{ pendingUrl?.href }}
            </p>

            <DialogFooter class="gap-2 sm:justify-between">
                <Button type="button" variant="ghost" @click="isOpen = false">Stay Here</Button>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <Button type="button" variant="outline" @click="openInNewTab">Open in New Tab</Button>
                    <Button type="button" @click="continueToSite">Continue</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
