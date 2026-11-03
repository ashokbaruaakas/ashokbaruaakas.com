export type SocialLink = {
    platform: string
    url: string
    icon: string
    label: string | null
    isPrimary: boolean
    inStructuredData: boolean
}

export type SkillCategory = {
    category: string
    items: string[]
}

export type ProjectLink = {
    label: string
    url: string
    isPrimary: boolean
}

export type Project = {
    name: string
    description: string
    owner: string | null
    repo: string | null
    technologies: string[]
    stars: number
    language: string
    demoUrl: string | null
    tier: number
    period: string | null
    role: string | null
    metric: string | null
    isPublicRepo: boolean
    linkLabel: string | null
    showOnResume: boolean
    links: ProjectLink[]
}

export type OpenSourceContribution = {
    title: string
    description: string
    organization: string
    type: string
    date: string
    tags: string[]
    url: string
    secondaryUrl: string | null
    metric: string | null
}

export type Experience = {
    role: string
    company: string
    description: string
    period: string
    url: string | null
    highlights: string[]
    isCurrent: boolean
}

export type Education = {
    degree: string
    school: string
    period: string
    institution: string | null
}

export type CaseStudy = {
    title: string
    context: string
    problem: string
    constraints: string[]
    design: string[]
    tradeoffs: string[]
    results: string[]
    technologies: string[]
}

export type Testimonial = {
    quote: string
    author: string
    role: string
    company: string
}

export type Language = {
    name: string
    level: string
}

export type Portfolio = {
    name: string
    tagline: string
    bio: string
    location: string
    availability: string
    about: string
    sideProjects: string
    githubUsername: string
    organization: string
    email: string
    phone: string
    socialLinks: SocialLink[]
    skills: SkillCategory[]
    projects: Project[]
    openSourceContributions: OpenSourceContribution[]
    experience: Experience[]
    education: Education[]
    languages: Language[]
    familiarSkills: string[]
    professionalSummary: string
    websiteUrl: string
    caseStudies: CaseStudy[]
    testimonials: Testimonial[]
    howIWork: string
    openSourceHighlights: string[]
    hasResume: boolean
    hasOpenSource: boolean
    visibleSections: string[]
    headline: string
    alternateNames: string[]
    city: string
    country: string
    timezone: string
    timezoneLabel: string
    phoneDisplay: string
    sourceUrl: string
    builtWith: string
    marqueeWords: string[]
    knowsAboutTopics: string[]
    openSourceInterests: string
    highlights: ProfileHighlight[]
    sections: Record<SectionKey, SectionContent>
    pages: Record<'home' | 'resume' | 'open-source', PageContent>
}

export type ProfileHighlight = {
    label: string
    value: string
    detail: string | null
    shortDetail: string | null
}

export type SectionKey = 'about' | 'skills' | 'work' | 'case-studies' | 'experience' | 'education' | 'testimonials' | 'connect' | 'open-source'

export type SectionContent = {
    eyebrow: string | null
    title: string
    description: string | null
    labels: Record<string, string>
}

export type PageContent = {
    title: string
    description: string
    imageAlt: string
}

export type SeoMetadata = {
    canonicalUrl: string
    homeUrl: string
    imageUrl: string | null
    photoUrl: string | null
}

export type PortfolioSection = {
    id: string
    label: string
}
