<?php

namespace Database\Factories;

use App\Enums\MediaKey;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => MediaKey::Avatar,
            'disk' => 'public',
            'path' => 'owner/'.fake()->uuid().'.webp',
            'mime_type' => 'image/webp',
            'size' => fake()->numberBetween(1000, 100000),
            'original_name' => fake()->word().'.webp',
        ];
    }
}
