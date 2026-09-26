export type SocialLink = {
    platform: string;
    url: string;
    icon: string;
    label: string | null;
};

export type SkillCategory = {
    category: string;
    items: string[];
};

export type Project = {
    name: string;
    description: string;
    owner: string | null;
    repo: string | null;
    technologies: string[];
    stars: number;
    language: string;
    demoUrl: string | null;
    tier: number;
    period: string | null;
    role: string | null;
    highlights: string[];
    metric: string | null;
    isPublicRepo: boolean;
    linkLabel: string | null;
    secondaryUrl: string | null;
    secondaryLabel: string | null;
};

export type OpenSourceContribution = {
    title: string;
    description: string;
    organization: string;
    type: string;
    date: string;
    tags: string[];
    url: string;
    secondaryUrl: string | null;
    metric: string | null;
};

export type Experience = {
    role: string;
    company: string;
    description: string;
    period: string;
    url: string | null;
    highlights: string[];
};

export type Education = {
    degree: string;
    school: string;
    period: string;
};

export type Language = {
    name: string;
    level: string;
};

export type Portfolio = {
    name: string;
    tagline: string;
    bio: string;
    location: string;
    currentWork: string;
    sideProjects: string;
    githubUsername: string;
    organization: string;
    email: string;
    phone: string;
    socialLinks: SocialLink[];
    skills: SkillCategory[];
    projects: Project[];
    openSourceContributions: OpenSourceContribution[];
    experience: Experience[];
    education: Education[];
    languages: Language[];
    familiarSkills: string[];
    professionalSummary: string;
    websiteUrl: string;
};

export type SeoMetadata = {
    canonicalUrl: string;
    homeUrl: string;
    imageUrl: string;
};

export type PortfolioSection = {
    id: string;
    label: string;
};
