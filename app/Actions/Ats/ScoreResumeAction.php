<?php

declare(strict_types=1);

namespace App\Actions\Ats;

use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\SocialLinkDTO;

/**
 * Scores the resume the way applicant tracking systems and recruiters read it: complete contact details and sections,
 * highlights that start with action verbs and show measurable results, and, when a job post is given, how many of its
 * keywords the resume contains.
 */
final readonly class ScoreResumeAction
{
    private const float JOB_MATCH_WEIGHT = 0.6;

    private const array ACTION_VERBS = [
        'accelerate', 'achieve', 'add', 'analyze', 'architect', 'audit', 'automate', 'boost', 'build', 'champion', 'coach',
        'collaborate', 'consolidate', 'containerize', 'contribute', 'convert', 'coordinate', 'create', 'cut', 'debug',
        'decrease', 'define', 'deliver', 'deploy', 'design', 'develop', 'direct', 'double', 'drive', 'eliminate', 'enable',
        'engineer', 'enhance', 'establish', 'expand', 'implement', 'improve', 'increase', 'integrate', 'introduce',
        'launch', 'lead', 'led', 'maintain', 'manage', 'mentor', 'migrate', 'model', 'modernize', 'monitor', 'negotiate',
        'optimize', 'orchestrate', 'own', 'pioneer', 'plan', 'publish', 'raise', 'rebuild', 'redesign', 'reduce',
        'refactor', 'replace', 'resolve', 'restructure', 'revamp', 'run', 'save', 'scale', 'secure', 'ship', 'simplify',
        'spearhead', 'speed', 'standardize', 'streamline', 'strengthen', 'test', 'train', 'transform', 'triple', 'tune',
        'unify', 'upgrade', 'win', 'won', 'write', 'wrote', 'built', 'ran', 'drove', 'grew', 'grow', 'made',
    ];

    private const array WEAK_OPENERS = ['responsible', 'worked', 'helped', 'assisted', 'involved', 'duties', 'tasked', 'participated', 'i', 'my', 'we'];

    /**
     * @param  list<string>|null  $jobKeywords  The keywords of a job post, or null to score the resume on its own.
     * @return array{score: int, resume_score: int, job_match_score: int|null, checks: list<array{key: string, label: string, points: int, max: int, tip: string|null}>, matched_keywords: list<string>|null, missing_keywords: list<string>|null}
     */
    public function handle(PortfolioDTO $portfolio, string $resumeText, ?array $jobKeywords = null): array
    {
        $highlights = array_merge([], ...array_map(fn (ExperienceDTO $experience): array => array_values($experience->highlights), $portfolio->experience));

        $checks = [
            $this->contact($portfolio),
            $this->check('headline', 'Headline', 5, filled($portfolio->headline ?: $portfolio->tagline) ? 1 : 0, 'Add a headline with the job title you target, such as "Senior Backend Engineer".'),
            $this->summary($portfolio->professionalSummary),
            $this->skills($portfolio),
            $this->experience($portfolio),
            $this->highlightCount($portfolio),
            $this->check('action_verbs', 'Highlights Start with Action Verbs', 10, $this->share($highlights, $this->startsWithActionVerb(...)), 'Start each highlight with a strong verb such as "Built", "Reduced" or "Led", not "Responsible for".'),
            $this->check('quantified', 'Highlights Show Measurable Results', 15, min(1, $this->share($highlights, fn (string $highlight): bool => preg_match('/\d/', $highlight) === 1) * 2), 'Add numbers to at least half the highlights: percentages, time saved, users, revenue or scale.'),
            $this->check('highlight_length', 'Highlights Fit on One Line', 5, $this->share($highlights, fn (string $highlight): bool => $this->wordCount($highlight) >= 8 && $this->wordCount($highlight) <= 30), 'Keep each highlight between 8 and 30 words.'),
            $this->check('pronouns', 'No First-Person Pronouns', 5, preg_match('/\b(I|me|my|we|our)\b/iu', implode("\n", [$portfolio->professionalSummary, ...$highlights])) === 1 ? 0 : 1, 'Drop "I", "my" and "we" from the summary and highlights.'),
            $this->skillsBackedByExperience($portfolio),
            $this->check('education', 'Education', 5, $portfolio->education !== [] ? 1 : 0, 'Add your education; many ATS filters require it.'),
        ];

        $resumeScore = array_sum(array_column($checks, 'points'));

        if ($jobKeywords === null || $jobKeywords === []) {
            return [
                'score' => $resumeScore,
                'resume_score' => $resumeScore,
                'job_match_score' => null,
                'checks' => $checks,
                'matched_keywords' => null,
                'missing_keywords' => null,
            ];
        }

        $matched = array_values(array_filter($jobKeywords, fn (string $keyword): bool => $this->contains($resumeText, $keyword)));
        $missing = array_values(array_diff($jobKeywords, $matched));
        $jobMatchScore = (int) round(count($matched) / count($jobKeywords) * 100);

        return [
            'score' => (int) round($jobMatchScore * self::JOB_MATCH_WEIGHT + $resumeScore * (1 - self::JOB_MATCH_WEIGHT)),
            'resume_score' => $resumeScore,
            'job_match_score' => $jobMatchScore,
            'checks' => $checks,
            'matched_keywords' => $matched,
            'missing_keywords' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function contact(PortfolioDTO $portfolio): array
    {
        $hasLinkedIn = array_any($portfolio->socialLinks, fn (SocialLinkDTO $link): bool => mb_strtolower($link->platform) === 'linkedin');
        $missing = array_keys(array_filter([
            'email' => $portfolio->email === '',
            'phone' => $portfolio->phone === '',
            'location' => $portfolio->location === '',
            'LinkedIn' => ! $hasLinkedIn,
        ]));
        $points = 10 - array_sum(array_map(fn (string $field): int => in_array($field, ['email', 'phone'], true) ? 3 : 2, $missing));

        return [
            'key' => 'contact',
            'label' => 'Contact Details',
            'points' => $points,
            'max' => 10,
            'tip' => $missing === [] ? null : 'Add your ' . implode(', ', $missing) . ' to the profile.',
        ];
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function summary(string $summary): array
    {
        $words = $this->wordCount($summary);
        $share = match (true) {
            $words >= 40 && $words <= 80 => 1,
            $words >= 20 && $words <= 120 => 0.5,
            default => 0,
        };

        return $this->check('summary', 'Professional Summary', 10, $share, $words === 0
            ? 'Add a 40 to 80 word professional summary with your title, core skills and a proven result.'
            : "Your summary has {$words} words; aim for 40 to 80.");
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function skills(PortfolioDTO $portfolio): array
    {
        $count = count($this->skillNames($portfolio));

        return $this->check('skills', 'Skills Section', 10, match (true) {
            $count >= 8 => 1,
            $count >= 4 => 0.5,
            default => 0,
        }, "List at least 8 skills by their standard names; you have {$count}.");
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function experience(PortfolioDTO $portfolio): array
    {
        if ($portfolio->experience === []) {
            return $this->check('experience', 'Work Experience with Dates', 10, 0, 'Add your work experience.');
        }

        $dated = $this->share($portfolio->experience, fn (ExperienceDTO $experience): bool => mb_trim($experience->period) !== '');

        return $this->check('experience', 'Work Experience with Dates', 10, $dated === 1.0 ? 1 : 0.5, 'Give every role a date range such as "Jan 2023 - Present".');
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function highlightCount(PortfolioDTO $portfolio): array
    {
        return $this->check(
            'highlight_count',
            'Three to Six Highlights per Role',
            10,
            $this->share($portfolio->experience, fn (ExperienceDTO $experience): bool => count($experience->highlights) >= 3 && count($experience->highlights) <= 6),
            'Give each role three to six highlights, most impressive first.',
        );
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function skillsBackedByExperience(PortfolioDTO $portfolio): array
    {
        $experienceText = implode("\n", array_map(
            fn (ExperienceDTO $experience): string => $experience->description . "\n" . implode("\n", $experience->highlights),
            $portfolio->experience,
        ));
        $share = $this->share($this->skillNames($portfolio), fn (string $skill): bool => $this->contains($experienceText, $skill));

        return $this->check('skills_in_context', 'Skills Used in Your Experience', 5, min(1, $share * 2), 'Name your key skills inside the experience highlights, not only in the skills list.');
    }

    /**
     * @return list<string>
     */
    private function skillNames(PortfolioDTO $portfolio): array
    {
        $skills = [];

        foreach ($portfolio->skills as $category) {
            array_push($skills, ...array_values($category->items));
        }

        return array_values(array_unique($skills));
    }

    /**
     * @return array{key: string, label: string, points: int, max: int, tip: string|null}
     */
    private function check(string $key, string $label, int $max, float|int $share, string $tip): array
    {
        $points = (int) round($max * max(0, min(1, $share)));

        return ['key' => $key, 'label' => $label, 'points' => $points, 'max' => $max, 'tip' => $points < $max ? $tip : null];
    }

    /**
     * The share of items that pass, or 0 when there are none.
     *
     * @template T
     *
     * @param  array<int, T>  $items
     * @param  callable(T): bool  $passes
     */
    private function share(array $items, callable $passes): float
    {
        if ($items === []) {
            return 0;
        }

        return count(array_filter($items, $passes)) / count($items);
    }

    private function startsWithActionVerb(string $highlight): bool
    {
        $first = mb_strtolower((string) preg_replace('/[^\p{L}-]/u', '', explode(' ', mb_trim($highlight))[0]));

        if ($first === '' || in_array($first, self::WEAK_OPENERS, true)) {
            return false;
        }

        return in_array($first, self::ACTION_VERBS, true)
            || in_array((string) preg_replace('/s$/', '', $first), self::ACTION_VERBS, true)
            || str_ends_with($first, 'ed');
    }

    private function contains(string $text, string $keyword): bool
    {
        return preg_match('/(?<![\p{L}\p{N}])' . preg_quote(mb_trim($keyword), '/') . '(?![\p{L}\p{N}])/iu', $text) === 1;
    }

    private function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', mb_trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
