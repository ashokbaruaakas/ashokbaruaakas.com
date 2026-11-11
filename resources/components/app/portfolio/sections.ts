import portfolioRoutes from '@/routes/portfolio'

export interface PortfolioSectionLink {
    key: string
    title: string
    href: string
}

export interface PortfolioSectionGroup {
    label: string
    items: PortfolioSectionLink[]
}

const list = (title: string, key: string): PortfolioSectionLink => ({ key, title, href: portfolioRoutes.lists.index.url(key) })

export const portfolioSectionGroups: PortfolioSectionGroup[] = [
    {
        label: 'About You',
        items: [
            { key: 'profile', title: 'Profile', href: portfolioRoutes.profile.edit.url() },
            { key: 'images', title: 'Images', href: portfolioRoutes.media.index.url() },
            { key: 'copy', title: 'Headings and SEO', href: portfolioRoutes.copy.edit.url() },
            { key: 'resume', title: 'Resume', href: portfolioRoutes.resume.edit.url() },
            list('Hero Stats', 'hero-stats'),
            list('Social Links', 'social-links'),
        ],
    },
    {
        label: 'Work',
        items: [list('Skills', 'skills'), list('Projects', 'projects'), list('Case Studies', 'case-studies'), list('Open Source', 'open-source')],
    },
    {
        label: 'Background',
        items: [
            list('Experience', 'experience'),
            list('Education', 'education'),
            list('Languages', 'languages'),
            list('Testimonials', 'testimonials'),
        ],
    },
]
