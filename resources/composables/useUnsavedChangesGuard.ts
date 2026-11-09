import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

/**
 * Ask before leaving the page while it has unsaved changes, whether by a link, the browser's back button or closing the tab.
 * Form submissions and other non-GET requests are not navigation, so they go through.
 */
export const useUnsavedChangesGuard = (hasUnsavedChanges: () => boolean, message = 'You have unsaved changes. Leave without saving?') => {
    let removeListener: (() => void) | null = null

    const warnBeforeUnload = (event: BeforeUnloadEvent) => {
        if (hasUnsavedChanges()) {
            event.preventDefault()
        }
    }

    onMounted(() => {
        removeListener = router.on('before', (event) => {
            if (event.detail.visit.method === 'get' && hasUnsavedChanges() && !window.confirm(message)) {
                event.preventDefault()
            }
        })

        window.addEventListener('beforeunload', warnBeforeUnload)
    })

    onUnmounted(() => {
        removeListener?.()
        window.removeEventListener('beforeunload', warnBeforeUnload)
    })
}
