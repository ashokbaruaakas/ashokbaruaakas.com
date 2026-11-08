<?php

declare(strict_types=1);

namespace App\Enums;

use TheSoftPulze\LaravibeStandards\Enums\Concerns\HasEnumMetadata;

/**
 * An AI provider the assistant can talk to. Each value is a provider name in `config/ai.php`.
 */
enum AiProvider: string
{
    use HasEnumMetadata;

    case Anthropic = 'anthropic';
    case OpenRouter = 'openrouter';

    public function label(): string
    {
        return match ($this) {
            self::Anthropic => 'Anthropic',
            self::OpenRouter => 'OpenRouter',
        };
    }

    /**
     * The model for conversations, tool use and longer writing, unless the owner picks another.
     */
    public function defaultChatModel(): string
    {
        return match ($this) {
            self::Anthropic => 'claude-sonnet-5-5',
            self::OpenRouter => 'anthropic/claude-sonnet-5.5',
        };
    }

    /**
     * The model for quick work such as reading details out of a job post, unless the owner picks another.
     */
    public function defaultFastModel(): string
    {
        return match ($this) {
            self::Anthropic => 'claude-haiku-5-5',
            self::OpenRouter => 'anthropic/claude-haiku-5.5',
        };
    }

    /**
     * Where the owner creates an API key for this provider.
     */
    public function keysUrl(): string
    {
        return match ($this) {
            self::Anthropic => 'https://console.anthropic.com/settings/keys',
            self::OpenRouter => 'https://openrouter.ai/settings/keys',
        };
    }

    /**
     * @return array{value: string, label: string, chatModel: string, fastModel: string, keysUrl: string}
     */
    public function toFormOption(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'chatModel' => $this->defaultChatModel(),
            'fastModel' => $this->defaultFastModel(),
            'keysUrl' => $this->keysUrl(),
        ];
    }
}
