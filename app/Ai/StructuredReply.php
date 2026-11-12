<?php

declare(strict_types=1);

namespace App\Ai;

use Laravel\Ai\Responses\StructuredAgentResponse;

final class StructuredReply
{
    /**
     * The structured reply of an agent. Models without structured output support often send the same JSON as plain
     * text, so that is read as a fallback.
     *
     * @return array<array-key, mixed>
     */
    public static function read(StructuredAgentResponse $response): array
    {
        if ($response->structured !== []) {
            return $response->structured;
        }

        $start = mb_strpos($response->text, '{');
        $end = mb_strrpos($response->text, '}');

        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $decoded = json_decode(mb_substr($response->text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : [];
    }
}
