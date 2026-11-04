import type { NavItem } from '@/types'
import { account } from '@/routes'
import { dashboard as administrationDashboard } from '@/routes/administration'
import administrationPortfolio from '@/routes/administration/portfolio'
import administrationSettings from '@/routes/administration/settings'
import { usePage } from '@inertiajs/vue3'
import { CircleGauge, IdCard, Settings, User } from '@lucide/vue'
import { computed } from 'vue'

/**
 * The administration navigation, shared by the tabs and command palette.
 *
 * An item is active on its own page and on any page nested below it, such as `/administration/settings/mail`.
 */
export function useAdministrationNavigation() {
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
        section('Dashboard', administrationDashboard.url(), CircleGauge),
        section('Portfolio', administrationPortfolio.profile.edit.url(), IdCard, '/administration/portfolio'),
        section('Settings', administrationSettings.general.edit.url(), Settings, '/administration/settings'),
        section('Account', account.url(), User),
    ]

    return { mainNavItems }
}
