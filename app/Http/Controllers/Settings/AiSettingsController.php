<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateAiSettingsAction;
use App\DTOs\PageMeta;
use App\DTOs\Settings\AiSettingsDTO;
use App\Enums\AiProvider;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
use App\Settings\AiSettings;
use App\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class AiSettingsController
{
    /**
     * Show the AI settings form. Saved API keys never leave the server; the form only learns whether one is set.
     */
    public function edit(AiSettings $settings, SettingsRepository $repository): Response
    {
        return vue('settings/Ai', [
            'settings' => [
                'provider' => $settings->provider->value,
                'chatModel' => $settings->chatModel,
                'fastModel' => $settings->fastModel,
            ],
            'apiKeys' => [
                'anthropic' => [
                    'isSaved' => $settings->anthropicApiKey !== null,
                    'isInEnv' => filled($repository->originalConfig('ai.providers.anthropic.key')),
                ],
                'openrouter' => [
                    'isSaved' => $settings->openRouterApiKey !== null,
                    'isInEnv' => filled($repository->originalConfig('ai.providers.openrouter.key')),
                ],
                'opencode-go' => [
                    'isSaved' => $settings->openCodeGoApiKey !== null,
                    'isInEnv' => filled($repository->originalConfig('ai.providers.opencode-go.key')),
                ],
            ],
            'providers' => array_map(fn (AiProvider $provider): array => $provider->toFormOption(), AiProvider::cases()),
            'isReady' => $settings->hasApiKey(),
        ], new PageMeta(
            title: 'AI Settings',
        ));
    }

    /**
     * Save the AI settings.
     */
    public function update(AiSettingsUpdateRequest $request, UpdateAiSettingsAction $action): RedirectResponse
    {
        $action->handle(AiSettingsDTO::from($request));

        toastSuccess('Settings saved.');

        return back();
    }
}
