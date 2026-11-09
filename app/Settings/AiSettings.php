<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\AiProvider;
use App\Settings\Attributes\Encrypted;

/**
 * Which AI provider and models the assistant uses.
 */
final class AiSettings extends Settings implements OverridesConfig
{
    public AiProvider $provider = AiProvider::Anthropic;

    /**
     * Null keeps `ANTHROPIC_API_KEY`.
     */
    #[Encrypted]
    public ?string $anthropicApiKey = null;

    /**
     * Null keeps `OPENROUTER_API_KEY`.
     */
    #[Encrypted]
    public ?string $openRouterApiKey = null;

    /**
     * Null keeps `OPENCODE_GO_API_KEY`.
     */
    #[Encrypted]
    public ?string $openCodeGoApiKey = null;

    /**
     * Null uses the provider's default chat model.
     */
    public ?string $chatModel = null;

    /**
     * Null uses the provider's default fast model.
     */
    public ?string $fastModel = null;

    /**
     * What the owner wants the assistant to know and follow, such as target roles and tone.
     */
    public ?string $ownerNotes = null;

    public static function group(): string
    {
        return 'ai';
    }

    /**
     * The chosen provider's default model becomes the chat model and its cheapest model the fast one,
     * so agents using `#[UseCheapestModel]` and the SDK's conversation titles follow these settings.
     */
    public function config(): array
    {
        $models = "ai.providers.{$this->provider->value}.models.text";

        return [
            'ai.default' => $this->provider->value,
            'ai.providers.anthropic.key' => $this->anthropicApiKey,
            'ai.providers.openrouter.key' => $this->openRouterApiKey,
            'ai.providers.opencode-go.key' => $this->openCodeGoApiKey,
            "{$models}.default" => $this->chatModel(),
            "{$models}.cheapest" => $this->fastModel(),
        ];
    }

    public function chatModel(): string
    {
        return $this->chatModel ?? $this->provider->defaultChatModel();
    }

    public function fastModel(): string
    {
        return $this->fastModel ?? $this->provider->defaultFastModel();
    }

    /**
     * Whether the chosen provider has an API key, from these settings or `.env`.
     */
    public function hasApiKey(): bool
    {
        return filled(config("ai.providers.{$this->provider->value}.key"));
    }
}
