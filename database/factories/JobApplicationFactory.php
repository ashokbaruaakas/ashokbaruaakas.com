<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
final class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_hunt_id' => JobHunt::factory(),
            'company' => fake()->company(),
            'role' => fake()->jobTitle(),
            'status' => JobApplicationStatus::Applied,
            'applied_on' => today()->subDays(3),
        ];
    }

    public function wishlist(): self
    {
        return $this->state([
            'status' => JobApplicationStatus::Wishlist,
            'applied_on' => null,
        ]);
    }
}
