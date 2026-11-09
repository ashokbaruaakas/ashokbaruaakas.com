import { createApp, type App } from 'vue'
import ExternalLinkDialog from './ExternalLinkDialog.vue'

export { externalUrlFromClick } from './externalLink'

let guardApp: App | null = null
let guardContainer: HTMLElement | null = null

/**
 * Ask before any link takes the visitor to another site, on every page.
 */
export const registerExternalLinkGuard = (): void => {
    if (guardApp !== null) {
        return
    }

    guardContainer = document.createElement('div')
    document.body.appendChild(guardContainer)
    guardApp = createApp(ExternalLinkDialog)
    guardApp.mount(guardContainer)
}

export const unregisterExternalLinkGuard = (): void => {
    guardApp?.unmount()
    guardApp = null
    guardContainer?.remove()
    guardContainer = null
}

if (import.meta.hot) {
    import.meta.hot.dispose(() => unregisterExternalLinkGuard())
}
