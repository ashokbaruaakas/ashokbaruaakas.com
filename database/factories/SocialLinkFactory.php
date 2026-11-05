<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
final class SocialLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform' => fake()->word(),
            'url' => fake()->url(),
            'icon' => fake()->word(),
            'label' => null,
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }
}
