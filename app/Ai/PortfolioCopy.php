<?php

declare(strict_types=1);

namespace App\Ai;

use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Enums\PortfolioFieldType as Type;
use App\Models\PageContent;
use App\Models\PortfolioSection as SectionCopy;

/**
 * The Headings and SEO copy as one flat set of fields, so the assistant and the form can treat it like the profile:
 * `page_{page}_{attribute}` for each public page's title and sharing text, and `section_{section}_{attribute}` for each
 * home page section's headings.
 */
final class PortfolioCopy
{
    public const string SECTION = 'copy';

    private const array PAGES = ['home' => 'Home', 'resume' => 'Resume', 'open-source' => 'Open Source'];

    private const array SECTIONS = [
        'about' => 'About',
        'skills' => 'Skills',
        'work' => 'Work',
        'case-studies' => 'Case Studies',
        'experience' => 'Experience',
        'education' => 'Education',
        'testimonials' => 'Testimonials',
        'connect' => 'Connect',
        'open-source' => 'Open Source',
    ];

    private const array WORK_LABELS = [
        'flagship' => 'Flagship Heading',
        'caseStudiesLink' => 'Case Studies Link',
        'supporting' => 'Supporting Heading',
        'supportingDescription' => 'Supporting Description',
    ];

    public static function pageField(string $page, string $attribute): string
    {
        return "page_{$page}_{$attribute}";
    }

    public static function sectionField(string $section, string $attribute): string
    {
        return "section_{$section}_{$attribute}";
    }

    /**
     * @return list<Field>
     */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::PAGES as $key => $name) {
            $group = "{$name} Page";
            $fields[] = new Field(self::pageField($key, 'title'), "{$group} Title", required: true, group: $group);
            $fields[] = new Field(self::pageField($key, 'description'), "{$group} Description", Type::Textarea, help: 'Shown by search engines and link previews.', group: $group, maxLength: 1000);
            $fields[] = new Field(self::pageField($key, 'image_alt'), "{$group} Share Image Description", group: $group);
        }

        foreach (self::SECTIONS as $key => $name) {
            $group = "{$name} Section";
            $fields[] = new Field(self::sectionField($key, 'eyebrow'), "{$name} Small Heading", group: $group);
            $fields[] = new Field(self::sectionField($key, 'title'), "{$name} Heading", required: true, group: $group);
            $fields[] = new Field(self::sectionField($key, 'description'), "{$name} Description", Type::Textarea, group: $group, maxLength: 1000);

            if ($key === 'work') {
                foreach (self::WORK_LABELS as $label => $labelName) {
                    $fields[] = new Field(self::sectionField($key, $label), "{$name} {$labelName}", group: $group);
                }
            }
        }

        return $fields;
    }

    /**
     * The copy as saved, with a page or section's plain name as its title until one is written.
     *
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        $pages = PageContent::query()->get()->keyBy('key');
        $sections = SectionCopy::query()->get()->keyBy('key');
        $values = [];

        foreach (self::PAGES as $key => $name) {
            $page = $pages->get($key);
            $values[self::pageField($key, 'title')] = $page->title ?? $name;
            $values[self::pageField($key, 'description')] = $page?->description;
            $values[self::pageField($key, 'image_alt')] = $page?->image_alt;
        }

        foreach (self::SECTIONS as $key => $name) {
            $section = $sections->get($key);
            $values[self::sectionField($key, 'eyebrow')] = $section?->eyebrow;
            $values[self::sectionField($key, 'title')] = $section->title ?? $name;
            $values[self::sectionField($key, 'description')] = $section?->description;

            if ($key === 'work') {
                foreach (array_keys(self::WORK_LABELS) as $label) {
                    $values[self::sectionField($key, $label)] = $section?->labels[$label] ?? null;
                }
            }
        }

        return $values;
    }

    /**
     * The section and page records the given field values change, with the rest of each record as saved.
     *
     * @param  array<string, mixed>  $changes
     * @return array{sections: array<string, array<string, mixed>>, pages: array<string, array<string, mixed>>}
     */
    public static function records(array $changes): array
    {
        $values = [...self::values(), ...$changes];
        $touches = fn (string $prefix): bool => array_any(array_keys($changes), fn (string $name): bool => str_starts_with($name, $prefix));
        $records = ['sections' => [], 'pages' => []];

        foreach (array_keys(self::PAGES) as $key) {
            if ($touches(self::pageField($key, ''))) {
                $records['pages'][$key] = [
                    'title' => $values[self::pageField($key, 'title')],
                    'description' => $values[self::pageField($key, 'description')],
                    'image_alt' => $values[self::pageField($key, 'image_alt')],
                ];
            }
        }

        foreach (array_keys(self::SECTIONS) as $key) {
            if ($touches(self::sectionField($key, ''))) {
                $records['sections'][$key] = [
                    'eyebrow' => $values[self::sectionField($key, 'eyebrow')],
                    'title' => $values[self::sectionField($key, 'title')],
                    'description' => $values[self::sectionField($key, 'description')],
                    'labels' => $key === 'work'
                        ? array_map(fn (string $label): mixed => $values[self::sectionField($key, $label)], array_combine(array_keys(self::WORK_LABELS), array_keys(self::WORK_LABELS)))
                        : null,
                ];
            }
        }

        return $records;
    }
}
