<?php

namespace Database\Factories;

use App\Models\PortfolioSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioSection>
 */
class PortfolioSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'eyebrow' => fake()->word(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
        ];
    }
}
