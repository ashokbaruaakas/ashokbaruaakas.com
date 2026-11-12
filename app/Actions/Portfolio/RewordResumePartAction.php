<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Ai\Agents\ResumeRewriter;
use App\Ai\StructuredReply;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\SkillCategory;
use RuntimeException;

final readonly class RewordResumePartAction
{
    private const int MAX_HIGHLIGHTS = 8;

    /**
     * Reword a resume's headline, summary or one role's highlights from the portfolio's facts, for the job the resume
     * is tailored for when it has one. Returns one line for a headline or a summary, one per highlight otherwise.
     *
     * @param  'headline'|'summary'|'highlights'  $part
     * @param  string|list<string>|null  $current  The part as the editor shows it; empty when it follows the portfolio.
     * @return list<string>
     *
     * @throws RuntimeException When the assistant sends nothing usable.
     */
    public function handle(string $part, string|array|null $current, ?Experience $experience = null, ?JobApplication $job = null): array
    {
        $read = StructuredReply::read(new ResumeRewriter()->prompt($this->prompt($part, $current, $experience, $job)));
        $lines = array_values(array_filter(array_map(
            fn (mixed $line): string => is_string($line) ? mb_trim($line) : '',
            is_array($read['lines'] ?? null) ? $read['lines'] : [],
        ), filled(...)));

        throw_if($lines === [], RuntimeException::class, 'The assistant sent no wording.');

        return $part === 'highlights' ? array_slice($lines, 0, self::MAX_HIGHLIGHTS) : [implode(' ', $lines)];
    }

    /**
     * @param  string|list<string>|null  $current
     */
    private function prompt(string $part, string|array|null $current, ?Experience $experience, ?JobApplication $job): string
    {
        $profile = Profile::query()->first();
        $skills = SkillCategory::query()->orderBy('sort_order')->get()
            ->map(fn (SkillCategory $category): string => "{$category->name}: " . implode(', ', $category->items))
            ->implode("\n");

        $sections = [
            'Part to reword' => match ($part) {
                'headline' => 'The headline, the line under the name, written as "Title — Focus".',
                'summary' => 'The professional summary.',
                default => "The highlights of the role {$experience?->role} at {$experience?->company}.",
            },
            'On the resume now' => is_array($current) ? implode("\n", $current) : $current,
            'Portfolio text it comes from' => match ($part) {
                'headline' => $profile?->tagline,
                'summary' => $profile?->professional_summary,
                default => implode("\n", $experience->highlights ?? []),
            },
            'Facts about this role' => $experience instanceof Experience ? implode("\n", array_filter([
                "{$experience->role} at {$experience->company}, {$experience->period}",
                $experience->description,
            ])) : null,
            'Roles' => $part === 'highlights' ? null : Experience::query()->orderBy('sort_order')->get()
                ->map(fn (Experience $role): string => "{$role->role} at {$role->company}, {$role->period}: " . implode(' ', $role->highlights))
                ->implode("\n"),
            'Skills' => $skills,
            'Tailored for' => $job instanceof JobApplication ? implode("\n", array_filter(["{$job->role} at {$job->company}", $job->notes])) : 'No particular job.',
        ];

        return collect($sections)
            ->filter(fn (?string $text): bool => filled($text))
            ->map(fn (string $text, string $heading): string => "{$heading}:\n{$text}")
            ->implode("\n\n");
    }
}
