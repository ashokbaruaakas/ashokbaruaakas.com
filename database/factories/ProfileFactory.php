<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'tagline' => fake()->sentence(),
            'bio' => fake()->paragraph(),
            'location' => fake()->city(),
            'availability' => fake()->sentence(3),
            'about' => fake()->paragraph(),
            'how_i_work' => fake()->paragraph(),
            'side_projects' => fake()->paragraph(),
            'open_source_highlights' => fake()->sentences(2),
            'github_username' => fake()->userName(),
            'organization' => fake()->userName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'familiar_skills' => fake()->words(3),
            'professional_summary' => fake()->paragraph(),
            'website_url' => fake()->url(),
        ];
    }
}
