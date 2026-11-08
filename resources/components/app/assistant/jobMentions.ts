import type { AssistantJob } from '@/types/assistant'

export interface JobMention {
    label: string
    job: AssistantJob
}

export type MessagePart = { text: string } | { label: string; jobId: number }

const TOKEN_PATTERN = /@\[([^\]\n]*)\]\(job:(\d+)\)/g

const escapeRegExp = (text: string): string => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

const tokenText = (text: string): string => text.replace(/[[\]()\n]/g, '').trim()

/**
 * What a mention looks like while typing: the company, plus the role when another job has the same company.
 */
export const mentionLabel = (job: AssistantJob, jobs: AssistantJob[]): string =>
    jobs.some(({ id, company }) => id !== job.id && company === job.company) ? `@${job.company} (${job.role})` : `@${job.company}`

/**
 * Swap each typed mention for the token the server and the assistant read, `@[Company — Role](job:ID)`.
 */
export const encodeMentions = (text: string, mentions: JobMention[]): string =>
    [...mentions]
        .sort((a, b) => b.label.length - a.label.length)
        .reduce(
            (encoded, { label, job }) =>
                encoded.replace(
                    new RegExp(`${escapeRegExp(label)}(?![\\w-])`, 'g'),
                    `@[${tokenText(job.company)} — ${tokenText(job.role)}](job:${job.id})`,
                ),
            text,
        )

/**
 * Split a message into plain text and job mentions.
 */
export const messageParts = (content: string): MessagePart[] => {
    const parts: MessagePart[] = []
    let last = 0

    for (const match of content.matchAll(TOKEN_PATTERN)) {
        if (match.index > last) {
            parts.push({ text: content.slice(last, match.index) })
        }

        parts.push({ label: match[1], jobId: Number(match[2]) })
        last = match.index + match[0].length
    }

    if (last < content.length) {
        parts.push({ text: content.slice(last) })
    }

    return parts
}
