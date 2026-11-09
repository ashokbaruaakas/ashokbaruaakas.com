<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Database\Eloquent\Model;

final class PortfolioMentions
{
    /**
     * A portfolio mention in a message, written as @[Section · Item](portfolio:SECTION/ID), or @[Profile](portfolio:profile).
     */
    public const string PATTERN = '/@\[[^\]\n]*\]\(portfolio:([a-z-]+)(?:\/(\d+))?\)/';

    /**
     * The portfolio parts a message mentions that still exist, in the order they first appear.
     *
     * @return list<array{section: PortfolioSection, item: Model}>
     */
    public static function in(string $message): array
    {
        preg_match_all(self::PATTERN, $message, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        $mentioned = [];

        foreach ($matches as $match) {
            $section = PortfolioSection::tryFrom($match[1]);
            $itemId = isset($match[2]) ? (int) $match[2] : null;
            $item = $section?->find($itemId);

            if ($section instanceof PortfolioSection && $item instanceof Model) {
                $mentioned["{$section->key}/{$itemId}"] = ['section' => $section, 'item' => $item];
            }
        }

        return array_values($mentioned);
    }
}
