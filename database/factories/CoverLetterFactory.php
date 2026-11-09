<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CoverLetter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoverLetter>
 */
final class CoverLetterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => null,
            'message_id' => null,
            'job_application_id' => null,
            'title' => 'Acme — Engineer',
            'recipient' => null,
            'body' => "Dear hiring team,\n\n" . fake()->paragraph(8) . "\n\nBest regards,\nAda",
        ];
    }
}
