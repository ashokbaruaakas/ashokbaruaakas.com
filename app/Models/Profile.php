<?php

declare(strict_types=1);

namespace App\Models;

use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Enums\PortfolioFieldType as Type;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string|null $headline
 * @property array<int, string>|null $alternate_names
 * @property string|null $city
 * @property string|null $country
 * @property string|null $timezone
 * @property string|null $timezone_label
 * @property string|null $phone_display
 * @property string|null $source_url
 * @property string|null $built_with
 * @property array<int, string>|null $marquee_words
 * @property array<int, string>|null $knows_about_topics
 * @property string|null $open_source_interests
 * @property string|null $tagline
 * @property string|null $bio
 * @property string|null $location
 * @property string|null $availability
 * @property string|null $about
 * @property string|null $how_i_work
 * @property string|null $side_projects
 * @property array<int, string>|null $open_source_highlights
 * @property string|null $github_username
 * @property string|null $organization
 * @property string|null $email
 * @property string|null $phone
 * @property array<int, string>|null $familiar_skills
 * @property string|null $professional_summary
 * @property string|null $website_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * Get the fields the owner edits, grouped for the form.
     *
     * @return array<int, Field>
     */
    public static function editableFields(): array
    {
        return [
            new Field('name', 'Name', required: true, group: 'Identity'),
            new Field('headline', 'Headline', help: 'Your job title, shown in the navigation.', group: 'Identity'),
            new Field('tagline', 'Tagline', help: 'Separate a second line with " — ".', group: 'Identity'),
            new Field('alternate_names', 'Other names', Type::Lines, help: 'One per line, for search engines.', group: 'Identity'),
            new Field('availability', 'Availability', group: 'Identity'),

            new Field('bio', 'Introduction', Type::Textarea, help: 'Wrap words in ** to make them bold.', group: 'About'),
            new Field('about', 'About', Type::Textarea, group: 'About'),
            new Field('how_i_work', 'How I work', Type::Textarea, group: 'About'),
            new Field('professional_summary', 'Professional summary', Type::Textarea, help: 'Shown at the top of your resume.', group: 'About'),
            new Field('familiar_skills', 'Also familiar with', Type::Lines, help: 'One per line.', group: 'About'),

            new Field('location', 'Location', help: 'Shown as written, such as "City, Country".', group: 'Location'),
            new Field('city', 'City', group: 'Location'),
            new Field('country', 'Country', group: 'Location'),
            new Field('timezone', 'Time zone', help: 'For your local time in the footer, such as Asia/Dhaka.', group: 'Location'),
            new Field('timezone_label', 'Time zone label', help: 'For example, UTC+6.', group: 'Location'),

            new Field('email', 'Email', Type::Email, group: 'Contact'),
            new Field('phone', 'Phone', help: 'In international format, used for call links.', group: 'Contact'),
            new Field('phone_display', 'Phone as displayed', group: 'Contact'),
            new Field('website_url', 'Website', Type::Url, group: 'Contact'),

            new Field('github_username', 'GitHub username', group: 'Open source'),
            new Field('organization', 'Organization', group: 'Open source'),
            new Field('side_projects', 'Side projects', Type::Textarea, help: 'Handles like @name are highlighted.', group: 'Open source'),
            new Field('open_source_highlights', 'Highlights', Type::Lines, help: 'One per line.', group: 'Open source'),
            new Field('open_source_interests', 'Interests', Type::Textarea, group: 'Open source'),

            new Field('source_url', 'Source code URL', Type::Url, group: 'Site'),
            new Field('built_with', 'Built with', group: 'Site'),
            new Field('marquee_words', 'Background words', Type::Lines, help: 'One per line.', group: 'Site'),
            new Field('knows_about_topics', 'Topics for search engines', Type::Lines, help: 'One per line.', group: 'Site'),
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'knows_about_topics' => 'array',
            'marquee_words' => 'array',
            'alternate_names' => 'array',
            'open_source_highlights' => 'array',
            'familiar_skills' => 'array',
        ];
    }
}
