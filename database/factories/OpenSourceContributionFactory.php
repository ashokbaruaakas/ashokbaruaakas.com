<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OpenSourceContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenSourceContribution>
 */
final class OpenSourceContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'organization' => fake()->company(),
            'type' => fake()->word(),
            'date' => fake()->date('F j, Y'),
            'tags' => fake()->words(2),
            'url' => fake()->url(),
            'secondary_url' => null,
            'metric' => null,
            'sort_order' => 0,
        ];
    }
}
