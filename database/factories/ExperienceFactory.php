<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->jobTitle(),
            'company' => fake()->company(),
            'description' => fake()->sentence(),
            'period' => '2020 — 2021',
            'url' => null,
            'highlights' => fake()->sentences(2),
            'sort_order' => 0,
        ];
    }
}
