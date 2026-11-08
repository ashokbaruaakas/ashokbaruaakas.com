<?php

declare(strict_types=1);

namespace App\Ai;

final class JobMentions
{
    /**
     * A job mention in a message, written as @[Company — Role](job:ID).
     */
    public const string PATTERN = '/@\[[^\]\n]*\]\(job:(\d+)\)/';

    /**
     * The ids of the jobs a message mentions, in the order they first appear.
     *
     * @return list<int>
     */
    public static function in(string $message): array
    {
        preg_match_all(self::PATTERN, $message, $matches);

        return array_values(array_unique(array_map(intval(...), $matches[1])));
    }
}
