import type { Portfolio, SeoMetadata } from '@/types/portfolio';

/** Skill categories whose items are listed as schema.org `knowsAbout` topics. */
const knowsAboutCategories = ['Languages', 'Frameworks', 'Databases'];

/** Domain topics that are not plain skills but describe the work itself. */
const knowsAboutDomains = [
    'Docker',
    'CI/CD',
    'HSM',
    'Payment gateways',
    'Multi-tenant SaaS',
];

/** Social platforms that identify the same person and belong in `sameAs`. */
const sameAsPlatforms = ['GitHub', 'LinkedIn', 'X', 'Telegram'];

/**
 * Build the schema.org Person node shared by every public page.
 */
export function buildPersonSchema(
    portfolio: Portfolio,
    seo: SeoMetadata,
): Record<string, unknown> {
    const [city, country] = portfolio.location
        .split(',')
        .map((part) => part.trim());
    const currentEmployer = portfolio.experience.find((entry) =>
        entry.period.includes('Present'),
    )?.company;

    return {
        '@type': 'Person',
        '@id': `${seo.homeUrl}#person`,
        name: portfolio.name,
        alternateName: ['Ashok Barua Akas', 'Akas'],
        jobTitle: 'Senior Full-Stack Engineer',
        url: seo.homeUrl,
        image: seo.imageUrl,
        email: portfolio.email,
        address: {
            '@type': 'PostalAddress',
            addressLocality: city,
            addressCountry: country,
        },
        sameAs: portfolio.socialLinks
            .filter(({ platform }) => sameAsPlatforms.includes(platform))
            .map(({ url }) => url),
        alumniOf: portfolio.education.map(({ school }) => ({
            '@type': 'EducationalOrganization',
            name: school.split(' - ')[0],
        })),
        knowsAbout: [
            ...new Set([
                ...portfolio.skills
                    .filter(({ category }) =>
                        knowsAboutCategories.includes(category),
                    )
                    .flatMap(({ items }) => items)
                    .filter((item) => !['HTML', 'CSS'].includes(item)),
                ...knowsAboutDomains,
            ]),
        ],
        ...(currentEmployer
            ? {
                  worksFor: {
                      '@type': 'Organization',
                      name: currentEmployer,
                  },
              }
            : {}),
    };
}

/**
 * Serialize a JSON-LD graph so it is safe to inline inside a script tag.
 */
export function serializeStructuredData(graph: unknown[]): string {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@graph': graph,
    }).replace(/</g, '\\u003c');
}
