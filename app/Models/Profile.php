<?php

declare(strict_types=1);

namespace App\Models;

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
