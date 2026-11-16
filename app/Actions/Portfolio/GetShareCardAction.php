<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\ShareCardDTO;
use App\Enums\MediaKey;
use App\Enums\SharePage;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\Profile;

final readonly class GetShareCardAction
{
    /**
     * Collect what the page's share image shows, or null before a profile exists.
     */
    public function handle(SharePage $page): ?ShareCardDTO
    {
        $profile = Profile::query()->first(['name', 'headline', 'location', 'city', 'country']);

        if (! $profile instanceof Profile) {
            return null;
        }

        $description = PageContent::query()->where('key', $page->value)->value('description');
        $portraitPath = Media::query()->where('key', MediaKey::Portrait640)->value('path');
        $location = $profile->location ?? implode(', ', array_filter([$profile->city, $profile->country]));

        return new ShareCardDTO(
            page: $page,
            name: $profile->name,
            label: $page->label(),
            headline: $profile->headline,
            description: is_string($description) ? $description : '',
            location: $location === '' ? null : $location,
            host: (string) parse_url(url('/'), PHP_URL_HOST),
            portraitPath: is_string($portraitPath) ? $portraitPath : null,
        );
    }
}
