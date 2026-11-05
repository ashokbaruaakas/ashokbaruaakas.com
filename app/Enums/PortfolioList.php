<?php

declare(strict_types=1);

namespace App\Enums;

use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Enums\PortfolioFieldType as Type;
use App\Models\CaseStudy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\OpenSourceContribution;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

enum PortfolioList: string
{
    use HasEnumMetadata;

    case Highlights = 'hero-stats';
    case SocialLinks = 'social-links';
    case Skills = 'skills';
    case Projects = 'projects';
    case CaseStudies = 'case-studies';
    case Experience = 'experience';
    case Education = 'education';
    case Languages = 'languages';
    case Testimonials = 'testimonials';
    case OpenSource = 'open-source';

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return match ($this) {
            self::Highlights => ProfileHighlight::class,
            self::SocialLinks => SocialLink::class,
            self::Skills => SkillCategory::class,
            self::Projects => Project::class,
            self::CaseStudies => CaseStudy::class,
            self::Experience => Experience::class,
            self::Education => Education::class,
            self::Languages => Language::class,
            self::Testimonials => Testimonial::class,
            self::OpenSource => OpenSourceContribution::class,
        };
    }

    /**
     * Determine whether single items can be hidden from the public pages.
     */
    public function canBeHidden(): bool
    {
        return in_array($this, [self::Projects, self::Testimonials], true);
    }

    /**
     * Get the attributes stored on the item itself, which excludes related
     * records such as a project's links.
     *
     * @return array<int, string>
     */
    public function storedAttributes(): array
    {
        $attributes = array_map(fn (Field $field): string => $field->name, array_filter($this->fields(), fn (Field $field): bool => $field->type !== Type::Links));

        return $this->canBeHidden() ? [...$attributes, 'is_visible'] : $attributes;
    }

    public function title(): string
    {
        return match ($this) {
            self::Highlights => 'Hero Stats',
            self::SocialLinks => 'Social Links',
            self::Skills => 'Skills',
            self::Projects => 'Projects',
            self::CaseStudies => 'Case Studies',
            self::Experience => 'Experience',
            self::Education => 'Education',
            self::Languages => 'Languages',
            self::Testimonials => 'Testimonials',
            self::OpenSource => 'Open Source',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Highlights => 'The short stats under your introduction on the home page.',
            self::SocialLinks => 'Where people can find you. Primary links get more prominence.',
            self::Skills => 'Skill categories on the home page and resume.',
            self::Projects => 'Tier 1 projects are flagship; tier 2 are supporting.',
            self::CaseStudies => 'In-depth write-ups on the home page.',
            self::Experience => 'Your work history on the home page and resume.',
            self::Education => 'Your education on the home page and resume.',
            self::Languages => 'Languages you speak.',
            self::Testimonials => 'What people say about working with you.',
            self::OpenSource => 'Your timeline on the open-source page.',
        };
    }

    /**
     * Get the column shown as each item's heading in the list.
     */
    public function titleField(): string
    {
        return match ($this) {
            self::Highlights => 'value',
            self::SocialLinks => 'platform',
            self::Skills, self::Projects, self::Languages => 'name',
            self::CaseStudies, self::OpenSource => 'title',
            self::Experience => 'role',
            self::Education => 'degree',
            self::Testimonials => 'author',
        };
    }

    /**
     * Get the column shown under each item's heading in the list.
     */
    public function subtitleField(): string
    {
        return match ($this) {
            self::Highlights => 'label',
            self::SocialLinks => 'url',
            self::Skills => 'items',
            self::Projects => 'period',
            self::CaseStudies => 'context',
            self::Experience => 'company',
            self::Education => 'school',
            self::Languages => 'level',
            self::Testimonials => 'company',
            self::OpenSource => 'date',
        };
    }

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Highlights => [
                new Field('value', 'Value', required: true, help: 'For example, 7+ yrs.'),
                new Field('label', 'Label', required: true, help: 'Read out by screen readers.'),
                new Field('detail', 'Detail'),
                new Field('short_detail', 'Short detail', help: 'Shown instead of the detail on small screens.'),
            ],
            self::SocialLinks => [
                new Field('platform', 'Platform', required: true),
                new Field('url', 'URL', Type::Url, required: true),
                new Field('icon', 'Icon', Type::Select, required: true, options: [
                    ['value' => 'linkedin', 'label' => 'LinkedIn'],
                    ['value' => 'github', 'label' => 'Code'],
                    ['value' => 'twitter', 'label' => 'At sign'],
                    ['value' => 'send', 'label' => 'Send'],
                    ['value' => 'message-circle', 'label' => 'Message circle'],
                    ['value' => 'message-square', 'label' => 'Message square'],
                ]),
                new Field('label', 'Label', help: 'Replaces the platform name when set.'),
                new Field('is_primary', 'Primary link', Type::Boolean),
                new Field('in_structured_data', 'Identifies you to search engines', Type::Boolean, help: 'Adds this profile to the page\'s structured data.'),
            ],
            self::Skills => [
                new Field('name', 'Category', required: true),
                new Field('items', 'Skills', Type::Lines, required: true, help: 'One per line.'),
            ],
            self::Projects => [
                new Field('name', 'Name', required: true),
                new Field('description', 'Description', Type::Textarea, required: true),
                new Field('language', 'Main language', required: true),
                new Field('tier', 'Tier', Type::Select, required: true, options: [
                    ['value' => '1', 'label' => 'Flagship'],
                    ['value' => '2', 'label' => 'Supporting'],
                ]),
                new Field('technologies', 'Technologies', Type::Lines, required: true, help: 'One per line.'),
                new Field('period', 'Period'),
                new Field('role', 'Your role', Type::Textarea),
                new Field('metric', 'Metric'),
                new Field('demo_url', 'Main link', Type::Url),
                new Field('link_label', 'Main link label'),
                new Field('owner', 'GitHub owner'),
                new Field('repo', 'GitHub repository'),
                new Field('stars', 'GitHub stars', Type::Number, required: true),
                new Field('is_public_repo', 'Public repository', Type::Boolean),
                new Field('show_on_resume', 'Show on resume', Type::Boolean),
                new Field('links', 'More links', Type::Links),
            ],
            self::CaseStudies => [
                new Field('title', 'Title', required: true),
                new Field('context', 'Context', required: true),
                new Field('problem', 'Problem', Type::Textarea, required: true),
                new Field('constraints', 'Constraints', Type::Lines, required: true, help: 'One per line.'),
                new Field('design', 'Design', Type::Lines, required: true, help: 'One per line.'),
                new Field('tradeoffs', 'Tradeoffs', Type::Lines, required: true, help: 'One per line.'),
                new Field('results', 'Results', Type::Lines, required: true, help: 'One per line.'),
                new Field('technologies', 'Technologies', Type::Lines, required: true, help: 'One per line.'),
            ],
            self::Experience => [
                new Field('role', 'Role', required: true),
                new Field('company', 'Company', required: true),
                new Field('period', 'Period', required: true),
                new Field('url', 'Company URL', Type::Url),
                new Field('description', 'Description', Type::Textarea),
                new Field('highlights', 'Highlights', Type::Lines, required: true, help: 'One per line.'),
                new Field('is_current', 'Current role', Type::Boolean),
            ],
            self::Education => [
                new Field('degree', 'Degree', required: true),
                new Field('school', 'School', required: true, help: 'Shown on the page, and can include the location.'),
                new Field('institution', 'Institution name', help: 'The name on its own, for search engines.'),
                new Field('period', 'Period', required: true),
            ],
            self::Languages => [
                new Field('name', 'Language', required: true),
                new Field('level', 'Level', required: true),
            ],
            self::Testimonials => [
                new Field('quote', 'Quote', Type::Textarea, required: true),
                new Field('author', 'Author', required: true),
                new Field('role', 'Role', required: true),
                new Field('company', 'Company', required: true),
            ],
            self::OpenSource => [
                new Field('title', 'Title', required: true),
                new Field('description', 'Description', Type::Textarea, required: true),
                new Field('organization', 'Organization', required: true),
                new Field('type', 'Type', required: true, help: 'For example, Project or Pull Request.'),
                new Field('date', 'Date', required: true),
                new Field('tags', 'Tags', Type::Lines, required: true, help: 'One per line. Used as filters on the page.'),
                new Field('url', 'URL', Type::Url, required: true),
                new Field('secondary_url', 'Second URL', Type::Url),
                new Field('metric', 'Metric'),
            ],
        };
    }
}
