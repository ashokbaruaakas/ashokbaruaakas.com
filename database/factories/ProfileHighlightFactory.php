<?php

namespace Database\Factories;

use App\Models\ProfileHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileHighlight>
 */
class ProfileHighlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'value' => fake()->word(),
            'detail' => fake()->sentence(3),
            'short_detail' => fake()->word(),
            'sort_order' => 0,
        ];
    }
}
