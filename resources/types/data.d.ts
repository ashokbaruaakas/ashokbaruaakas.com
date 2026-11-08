import { Nullable } from '.'

export interface Breadcrumb {
    label: string
    href: Nullable<string>
}

export interface PageMeta {
    heading?: string
    subheading?: string

    title?: string

    breadcrumbs?: Breadcrumb[]
}

export interface TwoFactorStatus {
    enabled: boolean
    pendingConfirmation: boolean
}

export interface BrowserSession {
    platform: string
    browser: string
    isDesktop: boolean
    isCurrentDevice: boolean
    lastActive: string
    ipAddress: Nullable<string>
}

export interface PortfolioAvailability {
    hasHome: boolean
    hasResume: boolean
    hasOpenSource: boolean
}

export interface GeneralSettings {
    siteName: Nullable<string>
    tagline: Nullable<string>
    supportEmail: Nullable<string>
}

export interface AuthenticationSettings {
    requireEmailVerification: boolean
}

export interface MailSettings {
    fromName: Nullable<string>
    fromAddress: Nullable<string>
}
