<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobHuntOutcome;
use App\Models\JobHunt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobHunt>
 */
final class JobHuntFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'goal' => null,
            'notes' => null,
            'started_on' => today()->subMonth(),
            'ended_on' => null,
            'outcome' => null,
        ];
    }

    public function ended(JobHuntOutcome $outcome = JobHuntOutcome::Hired): self
    {
        return $this->state([
            'ended_on' => today()->subWeek(),
            'outcome' => $outcome,
        ]);
    }
}
