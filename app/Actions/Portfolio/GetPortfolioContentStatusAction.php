<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\PortfolioList;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;

final readonly class GetPortfolioContentStatusAction
{
    /**
     * Determine which editable portfolio sections hold any content, keyed by section.
     *
     * @return array<string, bool>
     */
    public function handle(): array
    {
        $status = [
            'profile' => Profile::query()->exists(),
            'images' => Media::query()->exists(),
            'copy' => PortfolioSection::query()->exists() || PageContent::query()->exists(),
        ];

        foreach (PortfolioList::cases() as $list) {
            $status[$list->value] = $list->model()::query()->exists();
        }

        return $status;
    }
}
