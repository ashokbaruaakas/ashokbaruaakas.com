<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use App\Models\Resume;
use App\Models\ResumeBuild;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ResumeBuild>
 */
final class ResumeBuildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resume_id' => fn (): int => Resume::public()->id,
            'token' => (string) Str::ulid(),
            'status' => ResumePdfStatus::Scheduled,
            'scheduled_for' => now()->addMinutes(5),
        ];
    }

    /**
     * A finished build with its PDF on the disk.
     */
    public function ready(int $pageCount = 2, ?string $contentHash = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ResumePdfStatus::Ready,
            'scheduled_for' => now(),
            'template' => ResumeTemplate::Classic,
            'page_count' => $pageCount,
            'content_hash' => $contentHash ?? Str::random(64),
            'snapshot' => 'Resume text',
            'path' => 'resume-builds/' . Str::ulid() . '.pdf',
            'built_at' => now(),
        ]);
    }
}
