import { MENTION_TARGET } from '@/components/app/assistant/mentions'

export type Inline =
    | { type: 'text'; text: string }
    | { type: 'break' }
    | { type: 'code'; text: string }
    | { type: 'strong' | 'em' | 'del'; children: Inline[] }
    | { type: 'link'; href: string; children: Inline[] }
    | { type: 'mention'; label: string; target: string }

export type Alignment = 'left' | 'center' | 'right' | null

export type Block =
    | { type: 'paragraph'; content: Inline[] }
    | { type: 'heading'; level: number; content: Inline[] }
    | { type: 'code'; text: string }
    | { type: 'quote'; blocks: Block[] }
    | { type: 'list'; ordered: boolean; start: number; items: Block[][] }
    | { type: 'table'; header: Inline[][]; align: Alignment[]; rows: Inline[][][] }
    | { type: 'rule' }

const FENCE = /^\s*(`{3,}|~{3,})/
const HEADING = /^\s*(#{1,6})\s+(.*?)(?:\s+#+)?\s*$/
const RULE = /^\s*([-*_])(?:\s*\1){2,}\s*$/
const QUOTE = /^\s*>\s?/
const LIST_ITEM = /^(\s*)([-*+]|\d{1,9}[.)])\s+(.*)$/
const TABLE_DIVIDER = /^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/

const INLINE = new RegExp(
    [
        String.raw`@\[(?<mention>[^\]\n]*)\]\((?<target>${MENTION_TARGET})\)`,
        String.raw`\x60(?<code>[^\x60\n]+)\x60`,
        String.raw`\[(?<linkText>[^\]\n]+)\]\((?<href>(?:https?:\/\/|mailto:)[^\s)]+)\)`,
        String.raw`\*\*(?<strong>[^\n]+?)\*\*`,
        String.raw`(?<!\w)__(?<strongUnderscore>[^\n]+?)__(?!\w)`,
        String.raw`~~(?<del>[^\n]+?)~~`,
        String.raw`\*(?<em>[^\s*](?:[^*\n]*[^\s*])?)\*`,
        String.raw`(?<!\w)_(?<emUnderscore>[^\s_](?:[^_\n]*[^\s_])?)_(?!\w)`,
        String.raw`(?<url>https?:\/\/[^\s<]*[^\s<.,:;"')\]*_])`,
        String.raw`(?<break>\n)`,
    ].join('|'),
    'g',
)

const indentOf = (line: string): number => line.length - line.trimStart().length

const isTableStart = (lines: string[], index: number): boolean =>
    lines[index].includes('|') && index + 1 < lines.length && TABLE_DIVIDER.test(lines[index + 1]) && lines[index + 1].includes('-')

const startsBlock = (lines: string[], index: number): boolean =>
    [FENCE, HEADING, RULE, QUOTE, LIST_ITEM].some((pattern) => pattern.test(lines[index])) || isTableStart(lines, index)

const tableCells = (line: string): string[] =>
    line
        .trim()
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split('|')
        .map((cell) => cell.trim())

const alignmentOf = (cell: string): Alignment => {
    if (cell.startsWith(':') && cell.endsWith(':')) {
        return 'center'
    }

    if (cell.endsWith(':')) {
        return 'right'
    }

    return cell.startsWith(':') ? 'left' : null
}

/**
 * Split inline Markdown into text, emphasis, code, links and mentions.
 */
export const parseInline = (text: string): Inline[] => {
    const nodes: Inline[] = []
    let last = 0

    for (const match of text.matchAll(INLINE)) {
        const groups = match.groups ?? {}

        if (match.index > last) {
            nodes.push({ type: 'text', text: text.slice(last, match.index) })
        }

        if (groups.mention !== undefined) {
            nodes.push({ type: 'mention', label: groups.mention, target: groups.target })
        } else if (groups.code !== undefined) {
            nodes.push({ type: 'code', text: groups.code })
        } else if (groups.linkText !== undefined) {
            nodes.push({ type: 'link', href: groups.href, children: parseInline(groups.linkText) })
        } else if (groups.strong !== undefined || groups.strongUnderscore !== undefined) {
            nodes.push({ type: 'strong', children: parseInline(groups.strong ?? groups.strongUnderscore) })
        } else if (groups.del !== undefined) {
            nodes.push({ type: 'del', children: parseInline(groups.del) })
        } else if (groups.em !== undefined || groups.emUnderscore !== undefined) {
            nodes.push({ type: 'em', children: parseInline(groups.em ?? groups.emUnderscore) })
        } else if (groups.url !== undefined) {
            nodes.push({ type: 'link', href: groups.url, children: [{ type: 'text', text: groups.url }] })
        } else {
            nodes.push({ type: 'break' })
        }

        last = match.index + match[0].length
    }

    if (last < text.length) {
        nodes.push({ type: 'text', text: text.slice(last) })
    }

    return nodes
}

const parseList = (lines: string[], start: number): { block: Block; next: number } => {
    const first = lines[start].match(LIST_ITEM)!
    const indent = first[1].length
    const ordered = /\d/.test(first[2])
    const items: string[][] = []
    let contentIndent = 0
    let index = start

    while (index < lines.length) {
        const line = lines[index]
        const item = line.match(LIST_ITEM)

        if (item && item[1].length <= indent + 1 && /\d/.test(item[2]) === ordered) {
            items.push([item[3]])
            contentIndent = item[1].length + item[2].length + 1
            index++
            continue
        }

        if (line.trim() === '') {
            let ahead = index + 1

            while (ahead < lines.length && lines[ahead].trim() === '') {
                ahead++
            }

            const following = lines[ahead]?.match(LIST_ITEM)
            const continues =
                ahead < lines.length &&
                (indentOf(lines[ahead]) > indent || (following !== null && following !== undefined && /\d/.test(following[2]) === ordered))

            if (!continues) {
                break
            }

            items.at(-1)!.push('')
            index++
            continue
        }

        if (indentOf(line) > indent) {
            items.at(-1)!.push(line.slice(Math.min(indentOf(line), contentIndent)))
            index++
            continue
        }

        if (startsBlock(lines, index)) {
            break
        }

        items.at(-1)!.push(line)
        index++
    }

    return {
        block: { type: 'list', ordered, start: ordered ? Number.parseInt(first[2], 10) : 1, items: items.map((item) => parseBlocks(item)) },
        next: index,
    }
}

const parseBlocks = (lines: string[]): Block[] => {
    const blocks: Block[] = []
    let index = 0

    while (index < lines.length) {
        const line = lines[index]

        if (line.trim() === '') {
            index++
            continue
        }

        const fence = line.match(FENCE)

        if (fence) {
            const body: string[] = []
            index++

            while (index < lines.length && !lines[index].trim().startsWith(fence[1])) {
                body.push(lines[index++])
            }

            index++
            blocks.push({ type: 'code', text: body.join('\n') })
            continue
        }

        const heading = line.match(HEADING)

        if (heading) {
            blocks.push({ type: 'heading', level: heading[1].length, content: parseInline(heading[2]) })
            index++
            continue
        }

        if (RULE.test(line)) {
            blocks.push({ type: 'rule' })
            index++
            continue
        }

        if (QUOTE.test(line)) {
            const quoted: string[] = []

            while (index < lines.length && QUOTE.test(lines[index])) {
                quoted.push(lines[index++].replace(QUOTE, ''))
            }

            blocks.push({ type: 'quote', blocks: parseBlocks(quoted) })
            continue
        }

        if (LIST_ITEM.test(line)) {
            const { block, next } = parseList(lines, index)
            blocks.push(block)
            index = next
            continue
        }

        if (isTableStart(lines, index)) {
            const header = tableCells(line)
            const align = tableCells(lines[index + 1]).map(alignmentOf)
            const rows: Inline[][][] = []
            index += 2

            while (index < lines.length && lines[index].includes('|') && lines[index].trim() !== '') {
                rows.push(tableCells(lines[index++]).map(parseInline))
            }

            blocks.push({ type: 'table', header: header.map(parseInline), align, rows })
            continue
        }

        const paragraph = [line.trim()]
        index++

        while (index < lines.length && lines[index].trim() !== '' && !startsBlock(lines, index)) {
            paragraph.push(lines[index++].trim())
        }

        blocks.push({ type: 'paragraph', content: parseInline(paragraph.join('\n')) })
    }

    return blocks
}

/**
 * Parse the Markdown the assistant writes: headings, paragraphs, lists, quotes, code, tables and inline styles.
 */
export const parseMarkdown = (text: string): Block[] => parseBlocks(text.replace(/\r\n?/g, '\n').split('\n'))
