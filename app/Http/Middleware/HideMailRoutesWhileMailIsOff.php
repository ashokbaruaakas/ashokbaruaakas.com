<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers Fortify's password reset and email verification routes with a 404 while mail is off.
 * The routes stay registered so cached routes and generated Wayfinder files don't depend on `.env`.
 */
final class HideMailRoutesWhileMailIsOff
{
    /**
     * @var list<string>
     */
    private const array MAIL_ROUTES = [
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! config()->boolean('mail.enabled') && $request->routeIs(...self::MAIL_ROUTES), 404);

        return $next($request);
    }
}
