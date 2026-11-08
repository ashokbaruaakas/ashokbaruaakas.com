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
     * Null uses the provider's default chat model.
     */
    public ?string $chatModel = null;

    /**
     * Null uses the provider's default fast model.
     */
    public ?string $fastModel = null;

    public static function group(): string
    {
        return 'ai';
    }

    public function config(): array
    {
        return [
            'ai.default' => $this->provider->value,
            'ai.providers.anthropic.key' => $this->anthropicApiKey,
            'ai.providers.openrouter.key' => $this->openRouterApiKey,
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
