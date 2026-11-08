<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cookie;

/**
 * Public pages run without a session and are publicly cached, so the browser
 * reads this cookie to show the dashboard link. It grants no access: a stale
 * cookie only shows a link that leads to the login page.
 */
final class SyncSignedInCookie
{
    public const string NAME = 'signed_in';

    private const int MINUTES = 60 * 24 * 365;

    public function handleLogin(Login $event): void
    {
        Cookie::queue(Cookie::make(self::NAME, '1', self::MINUTES, httpOnly: false));
    }

    public function handleLogout(Logout $event): void
    {
        Cookie::queue(Cookie::forget(self::NAME));
    }
}
