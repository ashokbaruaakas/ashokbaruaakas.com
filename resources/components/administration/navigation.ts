import type { NavGroup, NavItem } from '@/types'
import { dashboard as administrationDashboard } from '@/routes/administration'
import administrationPortfolio from '@/routes/administration/portfolio'
import administrationSettings from '@/routes/administration/settings'
import { isNavItemActive } from '@/lib/helpers'
import { usePage } from '@inertiajs/vue3'
import { CircleGauge, FolderGit, IdCard, Settings } from '@lucide/vue'
import { computed } from 'vue'

/**
 * The administration navigation, shared by the sidebar, header and command palette.
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

    const mainNavGroups: NavGroup[] = [
        {
            label: 'Platform',
            items: [section('Dashboard', administrationDashboard.url(), CircleGauge)],
        },
        {
            label: 'Portfolio',
            items: [section('Portfolio', administrationPortfolio.profile.edit.url(), IdCard, '/administration/portfolio')],
        },
        {
            label: 'System',
            items: [section('Settings', administrationSettings.general.edit.url(), Settings, '/administration/settings')],
        },
    ]

    const footerNavItems: NavItem[] = [
        {
            title: 'Github Repo',
            href: 'https://github.com/thesoftpulze/laravibe-vue',
            icon: FolderGit,
        },
    ]

    const activeItem = computed(() => mainNavGroups.flatMap((group) => group.items).find((item) => isNavItemActive(item)))

    return { mainNavGroups, footerNavItems, activeItem }
}
