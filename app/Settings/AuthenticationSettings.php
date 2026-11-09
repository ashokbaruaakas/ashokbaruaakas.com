<?php

declare(strict_types=1);

namespace App\Settings;

/**
 * How people get access.
 */
final class AuthenticationSettings extends Settings
{
    /**
     * Whether routes behind the `verified` middleware need a verified email address.
     */
    public bool $requireEmailVerification = true;

    public static function group(): string
    {
        return 'authentication';
    }

    /**
     * Whether a verified email address is needed right now. Verification needs email, so it is never required while mail is off.
     */
    public function requiresEmailVerification(): bool
    {
        return $this->requireEmailVerification && config()->boolean('mail.enabled');
    }
}
