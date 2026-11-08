<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Ai\Agents\ConnectionCheck;
use App\Settings\AiSettings;

final readonly class CheckAiConnectionAction
{
    public function __construct(
        private AiSettings $ai,
    ) {}

    /**
     * Send a tiny prompt to the saved provider with its fast model, so a bad key or model surfaces straight away.
     */
    public function handle(): void
    {
        new ConnectionCheck()->prompt('Are you there?', provider: $this->ai->provider->value, model: $this->ai->fastModel());
    }
}
