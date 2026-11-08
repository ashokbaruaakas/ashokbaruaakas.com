<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CaseStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStudy>
 */
final class CaseStudyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'context' => fake()->sentence(3),
            'problem' => fake()->paragraph(),
            'constraints' => fake()->sentences(2),
            'design' => fake()->sentences(2),
            'tradeoffs' => fake()->sentences(2),
            'results' => fake()->sentences(2),
            'technologies' => fake()->words(3),
            'sort_order' => 0,
        ];
    }
}
