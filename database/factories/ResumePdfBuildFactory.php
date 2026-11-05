<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResumePdfStatus;
use App\Models\ResumePdfBuild;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ResumePdfBuild>
 */
final class ResumePdfBuildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => (string) Str::ulid(),
            'status' => ResumePdfStatus::Scheduled,
            'scheduled_for' => now()->addMinutes(5),
        ];
    }
}
