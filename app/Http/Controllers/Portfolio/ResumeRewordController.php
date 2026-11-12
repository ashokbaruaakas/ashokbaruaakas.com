<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\RewordResumePartAction;
use App\Ai\ProviderError;
use App\Http\Requests\Portfolio\ResumeRewordRequest;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Settings\AiSettings;
use Illuminate\Http\JsonResponse;
use Throwable;

final readonly class ResumeRewordController
{
    /**
     * Ask the assistant to reword one part of a resume, from the text the editor shows. The wording is returned for
     * the owner to edit and save; nothing is stored.
     */
    public function store(ResumeRewordRequest $request, Resume $resume, RewordResumePartAction $reword, AiSettings $settings): JsonResponse
    {
        if (! $settings->hasApiKey()) {
            return response()->json(['message' => "Add an API key for {$settings->provider->label()} under Settings, AI first."], 409);
        }

        /** @var 'headline'|'summary'|'highlights' $part */
        $part = $request->validated('part');
        $current = $request->string('current')->toString();

        try {
            $lines = $reword->handle(
                $part,
                $part === 'highlights' ? array_values(array_filter(explode("\n", $current), filled(...))) : $current,
                $request->filled('experience_id') ? Experience::query()->find($request->integer('experience_id')) : null,
                $request->filled('job_application_id') ? JobApplication::query()->find($request->integer('job_application_id')) : null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => ProviderError::describe($exception) ?? 'The assistant could not reword it. Please try again.'], 502);
        }

        return response()->json(['lines' => $lines]);
    }
}
