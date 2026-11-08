<?php

declare(strict_types=1);

namespace App\DTOs\Settings;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * The mail settings an administrator submits. A null value clears the setting.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class MailSettingsDTO implements Arrayable, Jsonable
{
    use AsDTO;

    public function __construct(
        public ?string $fromName = null,
        public ?string $fromAddress = null,
    ) {}
}
