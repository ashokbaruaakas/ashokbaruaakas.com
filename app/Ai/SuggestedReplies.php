<?php

declare(strict_types=1);

namespace App\Ai;

final class SuggestedReplies
{
    private const string PATTERN = '/<replies>(.*?)(?:<\/replies>|$)/s';

    private const int MOST = 4;

    private const int LONGEST = 120;

    /**
     * How the assistant offers answers to a question it asks, for the owner to send with one click.
     */
    public static function instructions(): string
    {
        return <<<'TEXT'
            - Whenever your reply asks the owner something with likely answers you can guess (yes or no, a choice between options you
              listed, which item or section to use), end the reply with those answers in a <replies> block, one per line, written as the
              owner would say them, at most four. The owner sends one with a click. Ask one such question at a time. For example:
              Should I add it to Backend & Architecture?
              <replies>
              Yes, add it there
              Put it in DevOps & Tools
              No, leave it out
              </replies>
              Leave the block out only when you cannot guess the answer, such as a fact only the owner knows.
            TEXT;
    }

    /**
     * Split an assistant message into the text to show and the answers it suggests.
     *
     * @return array{text: string, replies: list<string>}
     */
    public static function split(string $content): array
    {
        $replies = [];

        if (preg_match(self::PATTERN, $content, $match) === 1) {
            $replies = array_values(collect(preg_split('/\R/', $match[1]) ?: [])
                ->map(fn (string $line): string => mb_trim(preg_replace('/^\s*(?:[-*•]|\d+[.)])\s+/u', '', $line) ?? $line))
                ->filter(fn (string $line): bool => $line !== '' && mb_strlen($line) <= self::LONGEST)
                ->unique()
                ->take(self::MOST)
                ->all());
        }

        return ['text' => mb_trim(preg_replace(self::PATTERN, '', $content) ?? $content), 'replies' => $replies];
    }
}
