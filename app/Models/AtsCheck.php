<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AtsCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * One run of the ATS check on the resume, optionally against a pasted job post.
 *
 * @property int $id
 * @property int $score
 * @property int $resume_score
 * @property int|null $job_match_score
 * @property string|null $job_title
 * @property string|null $resume_name
 * @property list<array{key: string, label: string, points: int, max: int, tip: string|null}> $checks
 * @property list<string>|null $matched_keywords
 * @property list<string>|null $missing_keywords
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AtsCheck extends Model
{
    /** @use HasFactory<AtsCheckFactory> */
    use HasFactory;

    /**
     * @return array{score: int, resume_score: int, job_match_score: int|null, job_title: string|null, checks: list<array<string, mixed>>, matched_keywords: list<string>|null, missing_keywords: list<string>|null, checked_at: string|null}
     */
    public function summary(): array
    {
        return [
            'score' => $this->score,
            'resume_score' => $this->resume_score,
            'job_match_score' => $this->job_match_score,
            'job_title' => $this->job_title,
            'resume_name' => $this->resume_name,
            'checks' => $this->checks,
            'matched_keywords' => $this->matched_keywords,
            'missing_keywords' => $this->missing_keywords,
            'checked_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'checks' => 'array',
            'matched_keywords' => 'array',
            'missing_keywords' => 'array',
        ];
    }
}
