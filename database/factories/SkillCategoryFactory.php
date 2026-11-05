<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillCategory>
 */
final class SkillCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'items' => fake()->words(3),
            'sort_order' => 0,
        ];
    }
}
