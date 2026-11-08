<?php

declare(strict_types=1);

namespace App\DTOs\Settings;

use App\Enums\AiProvider;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * The AI settings an administrator submits. An empty API key keeps the saved one unless its removal is asked for.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class AiSettingsDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public AiProvider $provider,
        public ?string $anthropicApiKey = null,
        public ?string $openRouterApiKey = null,
        public bool $removeAnthropicApiKey = false,
        public bool $removeOpenRouterApiKey = false,
        public ?string $chatModel = null,
        public ?string $fastModel = null,
    ) {}
}
