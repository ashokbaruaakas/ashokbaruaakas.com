<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ats;

use App\Actions\Ats\ReadJobKeywordsAction;
use App\Actions\Ats\RunAtsCheckAction;
use App\Ai\ProviderError;
use App\Http\Requests\Ats\AtsCheckRequest;
use App\Models\Profile;
use App\Settings\AiSettings;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\AiException;

final class AtsCheckController
{
    /**
     * Score the resume for applicant tracking systems, and against a pasted job post's keywords when one is given.
     *
     * @throws ValidationException
     */
    public function store(AtsCheckRequest $request, AiSettings $settings, ReadJobKeywordsAction $readKeywords, RunAtsCheckAction $runCheck): RedirectResponse
    {
        if (! Profile::query()->exists()) {
            toastError('Fill in your profile before checking the resume.');

            return back();
        }

        $post = $request->validated('job_post');
        $job = null;

        if (is_string($post) && $post !== '') {
            $provider = $settings->provider->label();

            if (! $settings->hasApiKey()) {
                throw ValidationException::withMessages(['job_post' => "Add an API key for {$provider} under Settings, AI to match a job post."]);
            }

            try {
                $job = $readKeywords->handle($post);
            } catch (RequestException|AiException $exception) {
                throw ValidationException::withMessages(['job_post' => "{$provider} could not read the post: " . ProviderError::describe($exception)]);
            }

            if ($job['keywords'] === []) {
                throw ValidationException::withMessages(['job_post' => "{$provider} found no keywords in the post. Try again, or pick a stronger fast model under Settings, AI."]);
            }
        }

        $check = $runCheck->handle($job);

        toastSuccess("ATS score: {$check->score} / 100.");

        return back();
    }
}
