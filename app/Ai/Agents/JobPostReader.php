<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Reads a pasted job post and picks out the details the job form asks for. Runs on the fast model.
 */
#[UseCheapestModel]
#[MaxTokens(1024)]
#[Timeout(60)]
final class JobPostReader implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $today = today()->toDateString();

        return <<<TEXT
            Read the job post you are given and pick out its details. Copy what the post says; never guess.
            Use null for anything the post does not state.
            - company and role: as written in the post.
            - country: the country of the job, or "Remote" for a fully remote job open across countries.
            - location: the city and work mode, such as "Berlin, hybrid".
            - salary: the range and currency as written.
            - deadline: the date applications close, as YYYY-MM-DD. Today is {$today}.
            - link: the address of the post, only if it appears in the text.
            - summary: two or three short lines on what the job is and what it asks for.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->nullable()->required(),
            'role' => $schema->string()->nullable()->required(),
            'country' => $schema->string()->nullable()->required(),
            'location' => $schema->string()->nullable()->required(),
            'salary' => $schema->string()->nullable()->required(),
            'deadline' => $schema->string()->nullable()->required(),
            'link' => $schema->string()->nullable()->required(),
            'summary' => $schema->string()->nullable()->required(),
        ];
    }
}
