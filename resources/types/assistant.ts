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
}

export interface AssistantConversation {
    id: string
    title: string
    job: AssistantJob | null
    messages: AssistantMessage[]
}
