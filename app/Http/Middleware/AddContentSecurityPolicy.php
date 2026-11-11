<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class AddContentSecurityPolicy
{
    private const array SAME_ORIGIN_FRAMEABLE_ROUTES = ['resumes.preview'];

    /**
     * Attach a nonce-based Content-Security-Policy to every web response.
     *
     * The policy is skipped while the Vite dev server is running hot because
     * its assets and HMR socket are served from a different origin.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Vite::isRunningHot()) {
            return $next($request);
        }

        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            'frame-ancestors ' . ($request->routeIs(...self::SAME_ORIGIN_FRAMEABLE_ROUTES) ? "'self'" : "'none'"),
        ]));

        return $response;
    }
}
