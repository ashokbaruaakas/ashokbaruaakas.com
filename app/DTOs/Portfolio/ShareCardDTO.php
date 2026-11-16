<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use App\Enums\SharePage;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\File;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class ShareCardDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public const string VIEW = 'share.card';

    private const string TEMPLATE = 'blades/share/card.blade.php';

    public function __construct(
        public SharePage $page,
        public string $name,
        public ?string $label,
        public ?string $headline,
        public string $description,
        public ?string $location,
        public string $host,
        public ?string $portraitPath,
    ) {}

    /**
     * Get a short hash of everything drawn on the card, including the
     * template, so any change produces a new image and a new URL.
     */
    public function fingerprint(): string
    {
        $template = File::hash(resource_path(self::TEMPLATE));

        return mb_substr(hash('sha256', json_encode([$this->toArray(), $template], JSON_THROW_ON_ERROR)), 0, 16);
    }
}
