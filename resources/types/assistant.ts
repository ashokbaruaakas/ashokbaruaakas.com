export interface AssistantJob {
    id: number
    hunt_id: number
    hunt: string
    company: string
    role: string
}

export interface AssistantConversationSummary {
    id: string
    title: string
    job: AssistantJob | null
    updated_at: string | null
}

export interface AssistantMessage {
    id: string
    role: 'user' | 'assistant'
    content: string
    failed: boolean
    created_at: string | null
    proposals?: AssistantProposal[]
    coverLetters?: AssistantCoverLetter[]
}

export interface AssistantConversation {
    id: string
    title: string
    job: AssistantJob | null
    resume?: { id: number; name: string } | null
    messages: AssistantMessage[]
}

export type AssistantFieldValue = string | number | boolean | string[] | null

export interface AssistantProposal {
    id: number
    summary: string
    status: 'pending' | 'applied' | 'dismissed'
    section: string | null
    item: string | null
    target?: string
    adds_item: boolean
    is_stale: boolean
    link?: string | null
    link_label?: string
    preview_url?: string | null
    resume_id?: number | null
    changes?: ResumeChanges
    fields: {
        name: string
        label: string
        before: AssistantFieldValue
        after: AssistantFieldValue
    }[]
}

export interface AssistantCoverLetter {
    id: number
    title: string
    recipient: string | null
    body: string
    download_url: string
}

export interface AssistantPortfolioItem {
    target: string
    section: string
    label: string
}

export interface ResumeChanges {
    name?: string
    template?: string
    accent?: string | null
    font?: string | null
    job_application_id?: number | null
    experience?: number[]
    projects?: number[]
    education?: number[]
    skills?: number[]
    headline?: string | null
    summary?: string | null
    highlights?: Record<string, string[] | null>
}
