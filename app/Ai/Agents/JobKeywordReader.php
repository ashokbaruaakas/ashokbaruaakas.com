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
 * Picks out the keywords an applicant tracking system screens a job post's applicants for. Runs on the fast model.
 */
#[UseCheapestModel]
#[MaxTokens(1024)]
#[Timeout(60)]
final class JobKeywordReader implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
            Read the job post you are given and list the keywords an applicant tracking system would screen resumes for.
            - title: the job title as written in the post.
            - keywords: 10 to 25 hard skills, tools, languages, frameworks, platforms, methods and certifications the post
              asks for, most important first. Write each the shortest way the post writes it, such as "Laravel" or "AWS".
              Leave out soft skills, benefits, company names and generic words such as "software" or "experience".
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->nullable()->required(),
            'keywords' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
