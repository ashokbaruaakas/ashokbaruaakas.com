import { router } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'

/**
 * Hold back Inertia links while the page has unsaved changes, so the page can ask first, and have the browser warn before
 * the tab is closed or reloaded. Form submissions and other non-GET requests are not navigation, so they go through.
 */
export const useUnsavedChangesGuard = (hasUnsavedChanges: () => boolean) => {
    const blockedUrl = ref<string | null>(null)
    let isLeaving = false
    let removeListener: (() => void) | null = null

    const isConfirmingLeave = computed({
        get: () => blockedUrl.value !== null,
        set: (open) => {
            if (!open) {
                blockedUrl.value = null
            }
        },
    })

    const leave = () => {
        const url = blockedUrl.value

        if (url !== null) {
            isLeaving = true
            blockedUrl.value = null
            router.visit(url, { onFinish: () => (isLeaving = false) })
        }
    }

    const warnBeforeUnload = (event: BeforeUnloadEvent) => {
        if (hasUnsavedChanges()) {
            event.preventDefault()
        }
    }

    onMounted(() => {
        removeListener = router.on('before', (event) => {
            const { visit } = event.detail

            if (!isLeaving && visit.method === 'get' && hasUnsavedChanges()) {
                event.preventDefault()
                blockedUrl.value = visit.url.href
            }
        })

        window.addEventListener('beforeunload', warnBeforeUnload)
    })

    onUnmounted(() => {
        removeListener?.()
        window.removeEventListener('beforeunload', warnBeforeUnload)
    })

    return { isConfirmingLeave, leave }
}
