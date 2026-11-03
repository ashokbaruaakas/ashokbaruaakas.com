<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Models\PageContent;
use App\Models\PortfolioSection;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePortfolioCopyAction
{
    /**
     * Save the section and page copy.
     *
     * @param  array<string, array<string, mixed>>  $sections
     * @param  array<string, array<string, mixed>>  $pages
     */
    public function handle(array $sections, array $pages): void
    {
        DB::transaction(function () use ($sections, $pages): void {
            foreach ($sections as $key => $attributes) {
                PortfolioSection::query()->updateOrCreate(['key' => $key], $attributes);
            }

            foreach ($pages as $key => $attributes) {
                PageContent::query()->updateOrCreate(['key' => $key], $attributes);
            }
        });
    }
}
