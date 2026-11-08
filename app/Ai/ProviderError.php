<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\AiException;
use Throwable;

final class ProviderError
{
    /**
     * A message the owner can act on, such as "invalid x-api-key", or null for errors that are not the provider's.
     */
    public static function describe(Throwable $exception): ?string
    {
        if ($exception instanceof RequestException) {
            $message = $exception->response->json('error.message');

            return is_string($message) ? $message : $exception->getMessage();
        }

        return $exception instanceof AiException ? $exception->getMessage() : null;
    }
}
