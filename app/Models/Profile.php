<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
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
#[Fillable(['name', 'tagline', 'bio', 'location', 'availability', 'about', 'how_i_work', 'side_projects', 'open_source_highlights', 'github_username', 'organization', 'email', 'phone', 'familiar_skills', 'professional_summary', 'website_url'])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_source_highlights' => 'array',
            'familiar_skills' => 'array',
        ];
    }
}
