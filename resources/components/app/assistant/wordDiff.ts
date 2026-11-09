export interface DiffPart {
    type: 'same' | 'added' | 'removed'
    text: string
}

type Operation = { type: DiffPart['type'] | 'both'; text: string }

const MAX_CELLS = 1_000_000

const tokenize = (text: string): string[] => text.match(/\s+|[\p{L}\p{N}_'’-]+|[^\s\p{L}\p{N}_]/gu) ?? []

const isChange = (operation: Operation | undefined): boolean => operation !== undefined && operation.type !== 'same'

/**
 * Compare two texts word by word. Runs of changes come out as one removed part followed by one added part,
 * so a reworded phrase reads as a single replacement instead of alternating fragments.
 */
export const diffWords = (before: string, after: string): DiffPart[] => {
    const from = tokenize(before)
    const to = tokenize(after)

    if (from.length * to.length > MAX_CELLS) {
        return [
            { type: 'removed' as const, text: before },
            { type: 'added' as const, text: after },
        ].filter((part) => part.text !== '')
    }

    const width = to.length + 1
    const common = new Uint32Array((from.length + 1) * width)

    for (let i = from.length - 1; i >= 0; i--) {
        for (let j = to.length - 1; j >= 0; j--) {
            common[i * width + j] =
                from[i] === to[j] ? common[(i + 1) * width + j + 1] + 1 : Math.max(common[(i + 1) * width + j], common[i * width + j + 1])
        }
    }

    const operations: Operation[] = []
    let i = 0
    let j = 0

    while (i < from.length || j < to.length) {
        if (i < from.length && j < to.length && from[i] === to[j]) {
            operations.push({ type: 'same', text: from[i] })
            i++
            j++
        } else if (j < to.length && (i === from.length || common[i * width + j + 1] >= common[(i + 1) * width + j])) {
            operations.push({ type: 'added', text: to[j] })
            j++
        } else {
            operations.push({ type: 'removed', text: from[i] })
            i++
        }
    }

    operations.forEach((operation, index) => {
        if (operation.type === 'same' && operation.text.trim() === '' && isChange(operations[index - 1]) && isChange(operations[index + 1])) {
            operation.type = 'both'
        }
    })

    const parts: DiffPart[] = []
    let removed = ''
    let added = ''

    const flush = () => {
        if (removed) {
            parts.push({ type: 'removed', text: removed })
        }

        if (added) {
            parts.push({ type: 'added', text: added })
        }

        removed = ''
        added = ''
    }

    for (const operation of operations) {
        if (operation.type === 'same') {
            flush()
            const last = parts[parts.length - 1]

            if (last?.type === 'same') {
                last.text += operation.text
            } else {
                parts.push({ type: 'same', text: operation.text })
            }
        } else {
            removed += operation.type === 'added' ? '' : operation.text
            added += operation.type === 'removed' ? '' : operation.text
        }
    }

    flush()

    return parts
}
