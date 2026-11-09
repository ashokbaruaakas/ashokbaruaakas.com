import jobHuntRoutes from '@/routes/job-hunt'
import portfolioRoutes from '@/routes/portfolio'
import type { AssistantJob, AssistantPortfolioItem } from '@/types/assistant'

/**
 * Something the owner can mention with @: a job, the profile or a portfolio item.
 */
export interface Mentionable {
    target: string
    title: string
    detail: string
    name: string
    fullName: string
    token: string
}

export interface Mention {
    label: string
    mentionable: Mentionable
}

export type MessagePart = { text: string } | { label: string; target: string }

/**
 * What a mention points at inside its token: `job:ID`, `portfolio:SECTION/ID` or `portfolio:profile`.
 */
export const MENTION_TARGET = String.raw`job:\d+|portfolio:[a-z-]+(?:\/\d+)?`

const TOKEN_PATTERN = new RegExp(String.raw`@\[([^\]\n]*)\]\((${MENTION_TARGET})\)`, 'g')

const escapeRegExp = (text: string): string => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

const tokenText = (text: string): string => text.replace(/[[\]()\n]/g, '').trim()

export const mentionables = (jobs: AssistantJob[], items: AssistantPortfolioItem[]): Mentionable[] => [
    ...jobs.map((job) => ({
        target: `job:${job.id}`,
        title: job.company,
        detail: `${job.role} · ${job.hunt}`,
        name: job.company,
        fullName: `${job.company} (${job.role})`,
        token: `@[${tokenText(job.company)} — ${tokenText(job.role)}](job:${job.id})`,
    })),
    ...items.map((item) => ({
        target: item.target,
        title: item.label,
        detail: item.section,
        name: item.label,
        fullName: item.target === 'portfolio:profile' ? item.label : `${item.label} (${item.section})`,
        token:
            item.target === 'portfolio:profile'
                ? `@[Profile](${item.target})`
                : `@[${tokenText(item.section)} · ${tokenText(item.label)}](${item.target})`,
    })),
]

/**
 * What a mention looks like while typing: its name, plus what it is when another mentionable has the same name.
 */
export const mentionLabel = (mentionable: Mentionable, all: Mentionable[]): string =>
    all.some(({ target, name }) => target !== mentionable.target && name === mentionable.name) ? `@${mentionable.fullName}` : `@${mentionable.name}`

/**
 * Swap each typed mention for the token the server and the assistant read.
 */
export const encodeMentions = (text: string, mentions: Mention[]): string =>
    [...mentions]
        .sort((a, b) => b.label.length - a.label.length)
        .reduce((encoded, { label, mentionable }) => encoded.replace(new RegExp(`${escapeRegExp(label)}(?![\\w-])`, 'g'), mentionable.token), text)

/**
 * Where a mention leads: the job, or the portfolio page that edits the item. Null for a job that no longer exists.
 */
export const mentionHref = (target: string, jobs: AssistantJob[]): string | null => {
    const [kind, path] = target.split(':')

    if (kind === 'job') {
        const job = jobs.find(({ id }) => id === Number(path))

        return job ? jobHuntRoutes.show.url(job.hunt_id, { query: { job: job.id } }) : null
    }

    const [section] = path.split('/')

    return section === 'profile' ? portfolioRoutes.profile.edit.url() : portfolioRoutes.lists.index.url(section)
}

/**
 * Split a message into plain text and mentions.
 */
export const messageParts = (content: string): MessagePart[] => {
    const parts: MessagePart[] = []
    let last = 0

    for (const match of content.matchAll(TOKEN_PATTERN)) {
        if (match.index > last) {
            parts.push({ text: content.slice(last, match.index) })
        }

        parts.push({ label: match[1], target: match[2] })
        last = match.index + match[0].length
    }

    if (last < content.length) {
        parts.push({ text: content.slice(last) })
    }

    return parts
}
