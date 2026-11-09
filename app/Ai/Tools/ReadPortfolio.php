<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\PortfolioSection;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class ReadPortfolio implements Tool
{
    public function description(): string
    {
        return 'Read sections of the owner\'s portfolio as they are saved right now. List items carry the id to propose changes to them.';
    }

    public function handle(Request $request): string
    {
        $sections = array_values(array_filter(array_map(
            fn (mixed $key): ?PortfolioSection => is_string($key) ? PortfolioSection::tryFrom($key) : null,
            $request->array('sections'),
        )));

        if ($sections === []) {
            return 'Name at least one section to read: ' . implode(', ', PortfolioSection::keys()) . '.';
        }

        $portfolio = [];

        foreach ($sections as $section) {
            $portfolio[$section->key] = $section->read();
        }

        return json_encode($portfolio, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sections' => $schema->array()
                ->items($schema->string()->enum(PortfolioSection::keys()))
                ->description('The sections to read.')
                ->required(),
        ];
    }
}
