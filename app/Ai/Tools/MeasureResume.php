<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\Portfolio\MeasureResumeAction;
use App\Ai\ResumeChange;
use App\Ai\ResumeEditorContext;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;

final readonly class MeasureResume implements Tool
{
    public function __construct(
        private MeasureResumeAction $measureResume,
        private ResumeChange $resumeChange,
        private ?ResumeEditorContext $editor = null,
    ) {}

    public function description(): string
    {
        return 'Lay a resume out on A4 pages as its PDF would and get how many pages it takes and how full the last page is. '
            . 'Give the changes you are considering, in the same shape as ProposeResume, to measure the resume with them; nothing is proposed or saved. '
            . 'Leave the changes out to measure the resume as it is now (for the resume the owner is editing, with their unsaved changes). '
            . 'Use it to check a length before you propose it, and measure again after each adjustment.';
    }

    public function handle(Request $request): string
    {
        $resume = Resume::query()->active()->find($request->integer('resume_id'));

        if (! $resume instanceof Resume) {
            return 'There is no resume with that id. Call ListResumes for the ids.';
        }

        $data = $this->resumeChange->toEditorData(ResumeEditorContext::baseFor($resume, $this->editor), ResumeChange::fromToolRequest($request));

        try {
            $measured = $this->measureResume->handle($resume, $data);
        } catch (RuntimeException $exception) {
            report($exception);

            return 'The resume could not be measured right now. Estimate the length from its content instead, and say so.';
        }

        return json_encode([
            'pages' => $measured['pages'],
            'last_page_full_percent' => (int) round($measured['lastPageFill'] * 100),
        ], JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resume_id' => $schema->integer()->description('The resume to measure.')->required(),
            ...ResumeChange::toolSchema($schema),
        ];
    }
}
