<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\ResumeWriting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Rewords one part of a resume, such as its headline or a role's highlights, from the facts the portfolio holds.
 */
#[MaxTokens(8192)]
#[Timeout(120)]
final class ResumeRewriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $writing = ResumeWriting::rules() . "\n\n" . ResumeWriting::resumeRules();

        return <<<TEXT
            You reword one part of the owner's resume. You are given the part, its text on the resume now, the portfolio
            facts it may draw on, and the job the resume is tailored for, if any.
            - Use only the facts given; leave a claim out rather than guess it.
            - Keep what the current text gets right; sharpen and tighten it rather than starting over.
            - lines: the reworded part. One entry for a headline or a summary; one entry per highlight for highlights,
              most impressive first.

            {$writing}
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lines' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
