const statusClasses: Record<string, string> = {
    wishlist: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
    applied: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
    interviewing: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    offer: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    accepted: 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-emerald-950',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
}

const chanceClasses: Record<string, string> = {
    high: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    medium: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    low: 'bg-muted text-muted-foreground',
}

export const statusClass = (status: string): string => statusClasses[status] ?? 'bg-muted text-muted-foreground'

export const chanceClass = (chance: string): string => chanceClasses[chance] ?? 'bg-muted text-muted-foreground'

/**
 * Parse a `Y-m-d` date as a local calendar day, so it never shifts across timezones.
 */
const parseDay = (day: string): Date => new Date(`${day}T00:00:00`)

/**
 * A fixed locale, because the server renders these pages too and its locale can differ from the browser's.
 */
export const formatDay = (day: string, options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }): string =>
    new Intl.DateTimeFormat('en-GB', options).format(parseDay(day))

export const daysBetween = (from: string, to: string): number => Math.round((parseDay(to).getTime() - parseDay(from).getTime()) / 86_400_000)
