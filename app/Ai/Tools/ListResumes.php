<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\ResumeChange;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListResumes implements Tool
{
    public function __construct(private ResumeChange $resumeChange) {}

    public function description(): string
    {
        return 'List the owner\'s resumes with their id, template, colour, font, the page count of their latest PDF, the job each is tailored for, '
            . 'the ids of the portfolio items each one includes in order (read the items themselves with ReadPortfolio), and their rewording.';
    }

    public function handle(Request $request): string
    {
        $resumes = Resume::query()->active()->with('jobApplication')->orderByDesc('is_public')->orderBy('id')->get()
            ->map(fn (Resume $resume): array => [
                'id' => $resume->id,
                'name' => $resume->name,
                'is_public' => $resume->is_public,
                'template' => $resume->template->value,
                'accent' => $resume->design()['accent'],
                'font' => $resume->design()['font'],
                'page_count' => $resume->page_count,
                'tailored_for' => $resume->jobApplication === null ? null : ['id' => $resume->jobApplication->id, 'company' => $resume->jobApplication->company, 'role' => $resume->jobApplication->role],
                ...$this->resumeChange->current($resume, ['experience' => [], 'projects' => [], 'education' => [], 'skills' => [], 'headline' => null, 'summary' => null]),
                'highlights' => array_map(fn (array $override): array => $override['items'], $resume->overrides['highlights'] ?? []),
            ]);

        return $resumes->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
