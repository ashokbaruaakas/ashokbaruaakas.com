export interface AtsCheckItem {
    key: string
    label: string
    points: number
    max: number
    tip: string | null
}

export interface AtsCheck {
    score: number
    resume_score: number
    job_match_score: number | null
    job_title: string | null
    checks: AtsCheckItem[]
    matched_keywords: string[] | null
    missing_keywords: string[] | null
    checked_at: string | null
}
