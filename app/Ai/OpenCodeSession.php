<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

/**
 * OpenCode Go refuses requests that do not name their client and carry a stable `x-opencode-session` per conversation,
 * so requests to it are tagged as they leave. Scoped, so each request or job starts a fresh session.
 */
final class OpenCodeSession
{
    private string $id;

    public function __construct()
    {
        $this->id = Str::uuid()->toString();
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        $url = config('ai.providers.opencode-go.url');

        if (! is_string($url) || ! str_starts_with((string) $request->getUri(), mb_rtrim($url, '/'))) {
            return $request;
        }

        return $request
            ->withHeader('User-Agent', Str::slug(config()->string('app.name')) . '-assistant/1.0')
            ->withHeader('x-opencode-session', $this->id);
    }

    public function continue(string $conversationId): void
    {
        $this->id = $conversationId;
    }
}
