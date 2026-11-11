import type { Enum } from '@/types/enums'

export type JobApplicationStage = 'to_apply' | 'in_progress' | 'closed'

export interface JobApplicationStatusChoice {
    value: string
    label: string
    stage: JobApplicationStage
}

export interface JobHunt {
    id: number
    title: string
    goal: string | null
    work_modes: string[] | null
    countries: string | null
    open_to_relocation: boolean
    notes: string | null
    started_on: string
    ended_on: string | null
    outcome: string | null
}

export interface JobHuntSummary extends JobHunt {
    applications_count: number
    applied_count: number
    offers_count: number
}

export interface JobHuntUpcomingDate {
    id: number
    company: string
    role: string
    label: string
    date: string
}

export interface JobHuntDashboardSummary {
    id: number
    title: string
    started_on: string
    stages: Record<JobApplicationStage, number>
    interviewing: number
    offers: number
    needs_follow_up: number
    upcoming: JobHuntUpcomingDate[]
}

export interface JobApplicationEvent {
    id: number
    type: 'status_change' | 'note'
    from_status: string | null
    to_status: string | null
    body: string | null
    occurred_on: string
}

export interface ResumeBuildChoice {
    id: number
    label: string
    downloadUrl: string
}

export interface JobApplication {
    id: number
    resume_build_id: number | null
    company: string
    role: string
    status: string
    chance: string | null
    chance_note: string | null
    link: string | null
    country: string | null
    location: string | null
    work_mode: string | null
    relocation: string | null
    source: string | null
    salary: string | null
    contact: string | null
    applied_on: string | null
    deadline: string | null
    next_step: string | null
    next_step_on: string | null
    notes: string | null
    created_at: string
    updated_at: string
    events: JobApplicationEvent[]
    needs_follow_up: boolean
}

export type JobChanceOption = Enum
export type WorkModeOption = Enum
export type JobRelocationOption = Enum
export type JobHuntOutcomeOption = Enum
