/**
 * The external URL a click would open, or null when the click should go through untouched: it stays on this site,
 * isn't a plain left click (modifier keys and middle clicks already choose where the link opens), or isn't a web link.
 */
export const externalUrlFromClick = (event: MouseEvent, currentOrigin: string): URL | null => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return null
    }

    const target = event.target as Partial<Pick<Element, 'closest'>> | null
    const anchor = typeof target?.closest === 'function' ? (target.closest('a[href]') as Partial<HTMLAnchorElement> | null) : null

    // SVG links expose `href` as an object rather than a string.
    if (typeof anchor?.href !== 'string') {
        return null
    }

    let url: URL

    try {
        url = new URL(anchor.href, currentOrigin)
    } catch {
        return null
    }

    return ['http:', 'https:'].includes(url.protocol) && url.origin !== currentOrigin ? url : null
}
