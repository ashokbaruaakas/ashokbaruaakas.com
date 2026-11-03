<?php

namespace App\Http\Middleware;

use App\Actions\Portfolio\GetPortfolioAvailability;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortfolioPageIsAvailable
{
    public function __construct(private GetPortfolioAvailability $getPortfolioAvailability) {}

    /**
     * Stop pages that have no data yet. This must run before the cache headers
     * middleware so these responses are never stored by shared caches.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availability = $this->getPortfolioAvailability->handle();

        if ($request->routeIs('home') && ! $availability->hasProfile) {
            return Inertia::render('Welcome')->toResponse($request);
        }

        $isAvailable = match (true) {
            $request->routeIs('resume', 'resume.plain') => $availability->hasResume,
            $request->routeIs('open-source') => $availability->hasOpenSource,
            default => true,
        };

        abort_unless($isAvailable, 404);

        return $next($request);
    }
}
