import assistantRoutes from '@/routes/assistant'

const toolLabels: Record<string, string> = {
    ListJobHunts: 'Looking at your hunts…',
    ListJobApplications: 'Looking at your jobs…',
    ShowJobApplication: 'Reading a job…',
    ReadPortfolio: 'Reading your portfolio…',
    ReadWebPage: 'Reading the page…',
    ProposePortfolioChange: 'Preparing a change…',
    ProposeJobChange: 'Preparing a change…',
    ProposeNewJob: 'Preparing the job…',
    DraftCoverLetter: 'Writing the cover letter…',
    ListResumes: 'Looking at your resumes…',
    MeasureResume: 'Measuring the pages…',
    ProposeResume: 'Preparing the resume…',
}

export const toolActivity = (toolName: string | undefined): string => toolLabels[toolName ?? ''] ?? 'Working…'

// Some reasoning models send their thinking inside <think> tags as part of the reply.
export const visibleText = (content: string): string =>
    content
        .replace(/<think>[\s\S]*?(<\/think>|$)/g, '')
        .replace(/<\/think>/g, '')
        .trim()

const xsrfToken = (): string => {
    const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : ''
}

const errorMessage = async (response: Response): Promise<string> => {
    const data = (await response.json().catch(() => null)) as { message?: string } | null

    return data?.message ?? 'The assistant could not answer. Please try again.'
}

type StreamEvent = { type: string; delta?: string; tool_name?: string; message?: string }

const parseEvent = (data: string): StreamEvent => {
    try {
        return JSON.parse(data) as StreamEvent
    } catch {
        return { type: 'error', message: 'The assistant stopped before it finished. Please try again.' }
    }
}

/**
 * Send a message to the assistant and stream its reply. Resolves with the id of
 * the conversation the reply belongs to, and throws with a message to show when
 * the assistant cannot answer.
 */
export const streamAssistantReply = async (
    body: Record<string, unknown>,
    handlers: { onText: (delta: string) => void; onTool: (toolName: string | undefined) => void },
): Promise<string | null> => {
    const response = await fetch(assistantRoutes.messages.store.url(), {
        method: 'POST',
        headers: {
            Accept: 'text/event-stream',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    })

    if (!response.ok || !response.body) {
        throw new Error(await errorMessage(response))
    }

    const conversationId = response.headers.get('X-Conversation-Id') || (typeof body.conversation === 'string' ? body.conversation : null)
    const reader = response.body.pipeThrough(new TextDecoderStream()).getReader()
    let buffer = ''
    let hasText = false
    let isAfterTool = false

    for (;;) {
        const { done, value } = await reader.read()

        if (done) {
            break
        }

        buffer += value
        const chunks = buffer.split('\n\n')
        buffer = chunks.pop() ?? ''

        for (const chunk of chunks) {
            const data = chunk.replace(/^data: /, '')

            if (data === '[DONE]') {
                continue
            }

            const event = parseEvent(data)

            if (event.type === 'text_delta' && event.delta) {
                handlers.onText(hasText && isAfterTool ? `\n\n${event.delta}` : event.delta)
                hasText = true
                isAfterTool = false
            } else if (event.type === 'tool_call') {
                isAfterTool = true
                handlers.onTool(event.tool_name)
            } else if (event.type === 'error') {
                throw new Error(event.message ?? 'The assistant ran into a problem. Please try again.')
            }
        }
    }

    return conversationId
}
