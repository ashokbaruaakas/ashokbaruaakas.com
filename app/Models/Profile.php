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
 * @property string $tagline
 * @property string $bio
 * @property string $location
 * @property string $availability
 * @property string $about
 * @property string $how_i_work
 * @property string $side_projects
 * @property array<int, string> $open_source_highlights
 * @property string $github_username
 * @property string $organization
 * @property string $email
 * @property string $phone
 * @property array<int, string> $familiar_skills
 * @property string $professional_summary
 * @property string $website_url
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
