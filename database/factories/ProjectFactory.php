<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'owner' => null,
            'repo' => null,
            'technologies' => fake()->words(3),
            'stars' => 0,
            'language' => fake()->word(),
            'demo_url' => null,
            'tier' => 2,
            'period' => null,
            'role' => null,
            'metric' => null,
            'is_public_repo' => false,
            'link_label' => null,
            'show_on_resume' => true,
            'sort_order' => 0,
        ];
    }
}
