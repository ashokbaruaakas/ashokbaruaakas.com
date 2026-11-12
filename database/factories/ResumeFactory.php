<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResumeTemplate;
use App\Models\Resume;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Resume>
 */
final class ResumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word() . ' ' . fake()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'template' => ResumeTemplate::Classic,
            'page_count' => null,
            'is_public' => false,
        ];
    }
}
