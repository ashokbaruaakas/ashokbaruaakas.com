import type { NavItem } from '@/types'
import { account, dashboard } from '@/routes'
import portfolioRoutes from '@/routes/portfolio'
import settingsRoutes from '@/routes/settings'
import { usePage } from '@inertiajs/vue3'
import { CircleGauge, IdCard, Settings, User } from '@lucide/vue'
import { computed } from 'vue'

/**
 * The main navigation, shared by the tabs and command palette.
 *
 * An item is active on its own page and on any page nested below it, such as `/settings/mail`.
 */
export function useAppNavigation() {
    const page = usePage()
    const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname)

    const isCurrentSection = (href: string) => () => currentPath.value === href || currentPath.value.startsWith(`${href}/`)

    // A section whose landing page is nested, such as Settings opening on General, passes the section's own path.
    const section = (title: string, href: string, icon: NavItem['icon'], sectionHref = href): NavItem => ({
        title,
        href,
        icon,
        isActive: isCurrentSection(sectionHref),
    })

    const mainNavItems: NavItem[] = [
        section('Dashboard', dashboard.url(), CircleGauge),
        section('Portfolio', portfolioRoutes.profile.edit.url(), IdCard, '/portfolio'),
        section('Settings', settingsRoutes.general.edit.url(), Settings, '/settings'),
        section('Account', account.url(), User),
    ]

    return { mainNavItems }
}
