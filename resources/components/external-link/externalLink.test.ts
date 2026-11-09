import { describe, expect, it } from 'vite-plus/test'
import { externalUrlFromClick } from './externalLink'

const origin = 'https://portfolio.test'

const clickOn = (href: string | null, overrides: Partial<MouseEvent> = {}): MouseEvent =>
    ({
        defaultPrevented: false,
        button: 0,
        metaKey: false,
        ctrlKey: false,
        shiftKey: false,
        altKey: false,
        target: { closest: (selector: string) => (selector === 'a[href]' && href !== null ? { href } : null) },
        ...overrides,
    }) as unknown as MouseEvent

describe('externalUrlFromClick', () => {
    it('returns the URL of a link to another site', () => {
        expect(externalUrlFromClick(clickOn('https://github.com/someone'), origin)?.href).toBe('https://github.com/someone')
    })

    it('treats another subdomain as another site', () => {
        expect(externalUrlFromClick(clickOn('https://blog.portfolio.test/'), origin)).not.toBeNull()
    })

    it.each([
        ['a link on this site', 'https://portfolio.test/resume'],
        ['an email link', 'mailto:someone@example.com'],
        ['a phone link', 'tel:+15550100'],
        ['a click outside any link', null],
    ])('ignores %s', (_, href) => {
        expect(externalUrlFromClick(clickOn(href), origin)).toBeNull()
    })

    it.each([
        ['a modifier key', { metaKey: true }],
        ['a middle click', { button: 1 }],
        ['a click another handler already handled', { defaultPrevented: true }],
    ])('lets through %s', (_, overrides) => {
        expect(externalUrlFromClick(clickOn('https://github.com/', overrides), origin)).toBeNull()
    })
})
