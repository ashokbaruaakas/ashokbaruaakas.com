<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
final class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote' => fake()->sentence(),
            'author' => fake()->name(),
            'role' => fake()->jobTitle(),
            'company' => fake()->company(),
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }
}
