<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\AssistantTurn;
use App\Models\CoverLetter;
use App\Models\JobApplication;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class DraftCoverLetter implements Tool
{
    public function __construct(private AssistantTurn $turn) {}

    public function description(): string
    {
        return 'Save a cover letter the owner can download as a PDF. The PDF adds the owner\'s name, contact details and the date, '
            . 'so write only the greeting, the body and the sign-off. A revision is saved as a new letter.';
    }

    public function handle(Request $request): string
    {
        $jobId = $request->filled('job_id') ? $request->integer('job_id') : null;

        if ($jobId !== null && ! JobApplication::query()->whereKey($jobId)->exists()) {
            return "There is no job with id {$jobId}.";
        }

        $body = mb_trim($request->string('body')->toString());

        if (mb_strlen($body) < 100) {
            return 'Write the whole letter in the body.';
        }

        $letter = CoverLetter::query()->create([
            'job_application_id' => $jobId,
            'title' => $request->string('title')->limit(200)->toString(),
            'recipient' => $request->filled('recipient') ? $request->string('recipient')->limit(250)->toString() : null,
            'body' => $body,
        ]);

        $this->turn->created($letter);

        return 'The owner now sees the letter as a card under your reply, with a button to download it as a PDF. Do not repeat the letter in your reply.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'job_id' => $schema->integer()->description('The job the letter is for, if any.'),
            'title' => $schema->string()->description('A short name for the letter, such as "Acme — Senior Laravel Engineer".')->required(),
            'recipient' => $schema->string()->description('Who the letter is addressed to, with the company and address lines, one per line. Leave out when unknown.'),
            'body' => $schema->string()->description('The letter from the greeting to the sign-off. Separate paragraphs with a blank line.')->required(),
        ];
    }
}
