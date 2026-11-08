<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CheckAiConnectionAction;
use App\Settings\AiSettings;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Laravel\Ai\Exceptions\AiException;

final class AiConnectionCheckController
{
    /**
     * Check that the saved provider, API key and model can answer a prompt.
     */
    public function store(AiSettings $settings, CheckAiConnectionAction $action): RedirectResponse
    {
        $provider = $settings->provider->label();

        if (! $settings->hasApiKey()) {
            toastError("Add an API key for {$provider} first.");

            return back();
        }

        try {
            $action->handle();
        } catch (RequestException $exception) {
            $message = $exception->response->json('error.message');

            toastError("{$provider} could not be reached.")->description(is_string($message) ? $message : $exception->getMessage());

            return back();
        } catch (AiException $exception) {
            toastError("{$provider} could not be reached.")->description($exception->getMessage());

            return back();
        }

        toastSuccess("{$provider} answered using {$settings->fastModel()}.");

        return back();
    }
}
