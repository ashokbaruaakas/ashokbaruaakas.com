<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobApplicationEventType;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplicationEvent>
 */
final class JobApplicationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'type' => JobApplicationEventType::Note,
            'body' => fake()->sentence(),
            'occurred_on' => today(),
        ];
    }
}
