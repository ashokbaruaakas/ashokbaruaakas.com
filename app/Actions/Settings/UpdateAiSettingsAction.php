<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\DTOs\Settings\AiSettingsDTO;
use App\Settings\AiSettings;
use App\Settings\SettingsRepository;

final readonly class UpdateAiSettingsAction
{
    public function __construct(
        private SettingsRepository $settings,
        private AiSettings $ai,
    ) {}

    /**
     * Save the AI settings. API keys are never sent back to the form, so an empty key keeps the saved one.
     */
    public function handle(AiSettingsDTO $data): void
    {
        $this->settings->save((clone $this->ai)->fill([
            'provider' => $data->provider,
            'anthropicApiKey' => $this->apiKey($data->anthropicApiKey, $data->removeAnthropicApiKey, $this->ai->anthropicApiKey),
            'openRouterApiKey' => $this->apiKey($data->openRouterApiKey, $data->removeOpenRouterApiKey, $this->ai->openRouterApiKey),
            'openCodeGoApiKey' => $this->apiKey($data->openCodeGoApiKey, $data->removeOpenCodeGoApiKey, $this->ai->openCodeGoApiKey),
            'chatModel' => $data->chatModel,
            'fastModel' => $data->fastModel,
            'ownerNotes' => $data->ownerNotes,
        ]));
    }

    private function apiKey(?string $submitted, bool $remove, ?string $saved): ?string
    {
        if ($submitted !== null) {
            return $submitted;
        }

        return $remove ? null : $saved;
    }
}
