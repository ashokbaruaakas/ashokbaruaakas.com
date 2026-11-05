import type { PortfolioField, PortfolioFieldValue, PortfolioLinkValue } from '@/types/portfolio'

/**
 * Build a form's values from a saved record. "Lines" fields are edited as text, one item per line.
 */
export const toFormValues = (fields: PortfolioField[], record: Record<string, unknown> | null): Record<string, PortfolioFieldValue> =>
    Object.fromEntries(
        fields.map((field) => {
            const value = record?.[field.name]

            switch (field.type) {
                case 'lines':
                    return [field.name, Array.isArray(value) ? value.join('\n') : '']
                case 'links':
                    return [
                        field.name,
                        Array.isArray(value)
                            ? (value as PortfolioLinkValue[]).map(({ label, url, is_primary }) => ({ label, url, is_primary: Boolean(is_primary) }))
                            : [],
                    ]
                case 'boolean':
                    return [field.name, Boolean(value)]
                case 'number':
                    return [field.name, typeof value === 'number' ? value : 0]
                case 'select':
                    return [field.name, typeof value === 'string' || typeof value === 'number' ? `${value}` : (field.options[0]?.value ?? '')]
                default:
                    return [field.name, typeof value === 'string' ? value : '']
            }
        }),
    )

/**
 * Turn a form's values back into what the server validates.
 */
export const toPayload = (fields: PortfolioField[], values: Record<string, PortfolioFieldValue>): Record<string, unknown> =>
    Object.fromEntries(
        fields.map((field) => {
            const value = values[field.name]

            if (field.type === 'lines') {
                return [
                    field.name,
                    (typeof value === 'string' ? value : '')
                        .split('\n')
                        .map((line) => line.trim())
                        .filter(Boolean),
                ]
            }

            return [field.name, value]
        }),
    )

/**
 * Find the first error for a field, including errors on its individual lines or links.
 */
export const fieldError = (errors: Record<string, string | undefined>, name: string): string | undefined =>
    errors[name] ?? Object.entries(errors).find(([key]) => key.startsWith(`${name}.`))?.[1]
