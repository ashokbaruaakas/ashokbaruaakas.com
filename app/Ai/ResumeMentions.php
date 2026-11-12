<?php

declare(strict_types=1);

namespace App\Ai;

final class ResumeMentions
{
    /**
     * A resume mention in a message, written as @[Resumes · Name](resume:ID).
     */
    public const string PATTERN = '/@\[[^\]\n]*\]\(resume:(\d+)\)/';

    /**
     * The ids of the resumes a message mentions, in the order they first appear.
     *
     * @return list<int>
     */
    public static function in(string $message): array
    {
        preg_match_all(self::PATTERN, $message, $matches);

        return array_values(array_unique(array_map(intval(...), $matches[1])));
    }
}
