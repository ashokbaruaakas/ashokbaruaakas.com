<?php

declare(strict_types=1);

namespace App\Actions\Ats;

use App\Ai\Agents\JobKeywordReader;
use App\Ai\StructuredReply;
use Illuminate\Support\Str;

final readonly class ReadJobKeywordsAction
{
    private const int MAX_KEYWORDS = 30;

    /**
     * The job title and the keywords an ATS screens the post's applicants for, without duplicates.
     *
     * @return array{title: string|null, keywords: list<string>}
     */
    public function handle(string $post): array
    {
        $read = StructuredReply::read(new JobKeywordReader()->prompt($post));
        $keywords = [];

        foreach (is_array($read['keywords'] ?? null) ? $read['keywords'] : [] as $keyword) {
            $keyword = is_string($keyword) ? Str::limit(mb_trim($keyword), 60, '') : '';

            if ($keyword !== '' && ! array_any($keywords, fn (string $kept): bool => mb_strtolower($kept) === mb_strtolower($keyword))) {
                $keywords[] = $keyword;
            }
        }

        $title = is_string($read['title'] ?? null) && mb_trim($read['title']) !== '' ? Str::limit(mb_trim($read['title']), 200, '') : null;

        return ['title' => $title, 'keywords' => array_slice($keywords, 0, self::MAX_KEYWORDS)];
    }
}
