import type { Portfolio, SeoMetadata } from '@/types/portfolio';

/**
 * Build the schema.org Person node shared by every public page.
 */
export function buildPersonSchema(
    portfolio: Portfolio,
    seo: SeoMetadata,
): Record<string, unknown> {
    const currentEmployer = portfolio.experience.find(
        ({ isCurrent }) => isCurrent,
    )?.company;

    return {
        '@type': 'Person',
        '@id': `${seo.homeUrl}#person`,
        name: portfolio.name,
        alternateName: portfolio.alternateNames,
        jobTitle: portfolio.headline,
        url: seo.homeUrl,
        image: seo.photoUrl ?? undefined,
        email: portfolio.email,
        address: {
            '@type': 'PostalAddress',
            addressLocality: portfolio.city,
            addressCountry: portfolio.country,
        },
        sameAs: portfolio.socialLinks
            .filter(({ inStructuredData }) => inStructuredData)
            .map(({ url }) => url),
        alumniOf: portfolio.education.map(({ school, institution }) => ({
            '@type': 'EducationalOrganization',
            name: institution ?? school,
        })),
        knowsAbout: portfolio.knowsAboutTopics,
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
