<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AtsCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtsCheck>
 */
final class AtsCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'score' => 72,
            'resume_score' => 72,
            'job_match_score' => null,
            'job_title' => null,
            'checks' => [
                ['key' => 'contact', 'label' => 'Contact Details', 'points' => 10, 'max' => 10, 'tip' => null],
            ],
            'matched_keywords' => null,
            'missing_keywords' => null,
        ];
    }
}
