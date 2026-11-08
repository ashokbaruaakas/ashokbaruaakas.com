<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Sends the smallest useful prompt, to prove the provider, key and model work together.
 */
#[MaxTokens(16)]
#[Timeout(20)]
final class ConnectionCheck implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Reply with the single word OK.';
    }
}
