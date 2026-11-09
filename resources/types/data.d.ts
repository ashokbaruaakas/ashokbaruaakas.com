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

export interface AuthenticationSettings {
    requireEmailVerification: boolean
}

export interface MailSettings {
    fromName: Nullable<string>
    fromAddress: Nullable<string>
}

export type AiProviderValue = 'anthropic' | 'openrouter' | 'opencode-go'

export interface AiSettings {
    provider: AiProviderValue
    chatModel: Nullable<string>
    fastModel: Nullable<string>
    ownerNotes: Nullable<string>
}

export interface AiProviderOption {
    value: AiProviderValue
    label: string
    chatModel: string
    fastModel: string
    keysUrl: string
}

export interface AiApiKeyStatus {
    isSaved: boolean
    isInEnv: boolean
}
