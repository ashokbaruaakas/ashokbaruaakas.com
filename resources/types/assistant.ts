export interface AssistantConversationSummary {
    id: string
    title: string
    updated_at: string | null
}

export interface AssistantMessage {
    id: string
    role: 'user' | 'assistant'
    content: string
    failed: boolean
}

export interface AssistantConversation {
    id: string
    title: string
    messages: AssistantMessage[]
}
