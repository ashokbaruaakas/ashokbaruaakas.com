<?php

declare(strict_types=1);

namespace App\Actions\Ats;

use App\Ai\Agents\JobKeywordReader;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;

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
        $read = $this->read(new JobKeywordReader()->prompt($post));
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

    /**
     * Models without structured output support often send the same JSON as plain text, so that is read as a fallback.
     *
     * @return array<array-key, mixed>
     */
    private function read(StructuredAgentResponse $response): array
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
