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
    owner: string;
    repo: string;
    technologies: string[];
    stars: number;
    language: string;
    demoUrl: string | null;
};

export type Experience = {
    role: string;
    company: string;
    description: string;
    period: string;
    url: string | null;
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
    socialLinks: SocialLink[];
    skills: SkillCategory[];
    projects: Project[];
    experience: Experience[];
};

export type PortfolioSection = {
    id: string;
    label: string;
};
