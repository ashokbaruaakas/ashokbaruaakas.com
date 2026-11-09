<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\AuthenticationSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel's `verified` middleware, which lets every user through while email verification is not required or mail is off.
 */
final readonly class EnsureEmailIsVerified
{
    public function __construct(
        private AuthenticationSettings $settings,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = $request->user();

        if ($this->settings->requiresEmailVerification() && (! $user || ! $user->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
