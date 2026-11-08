<?php

declare(strict_types=1);

namespace App\Http\Controllers\JobHunt;

use App\Actions\JobHunt\ReadJobPostAction;
use App\Ai\ProviderError;
use App\Http\Requests\JobHunt\JobPostReadRequest;
use App\Settings\AiSettings;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\AiException;

final class JobPostReaderController
{
    /**
     * Read a pasted job post and return the job form fields it fills. Problems come back as an error on the text,
     * so the form shows them where the post was pasted.
     *
     * @throws ValidationException
     */
    public function store(JobPostReadRequest $request, AiSettings $settings, ReadJobPostAction $action): JsonResponse
    {
        $provider = $settings->provider->label();

        if (! $settings->hasApiKey()) {
            throw ValidationException::withMessages(['text' => "Add an API key for {$provider} under Settings, AI to read job posts."]);
        }

        /** @var string $post */
        $post = $request->validated('text');

        try {
            $details = $action->handle($post);
        } catch (RequestException|AiException $exception) {
            throw ValidationException::withMessages(['text' => "{$provider} could not read the post: " . ProviderError::describe($exception)]);
        }

        if (array_filter($details) === []) {
            throw ValidationException::withMessages(['text' => "{$provider} found no job details in the post. Try again, or pick a stronger fast model under Settings, AI."]);
        }

        return response()->json($details);
    }
}
