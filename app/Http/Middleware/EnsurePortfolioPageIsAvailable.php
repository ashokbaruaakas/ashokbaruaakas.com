<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePortfolioPageIsAvailable
{
    public function __construct(private GetPortfolioAvailabilityAction $getPortfolioAvailability) {}

    /**
     * Stop pages that have no data yet. This must run before the cache headers
     * middleware so these responses are never stored by shared caches.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availability = $this->getPortfolioAvailability->handle();

        if ($request->routeIs('home') && ! $availability->hasHome) {
            return vue('Welcome', ['hasOwner' => User::query()->exists()])->toResponse($request);
        }

        $isAvailable = match (true) {
            $request->routeIs('resume', 'resume.plain', 'resume.pdf') => $availability->hasResume,
            $request->routeIs('open-source') => $availability->hasOpenSource,
            default => true,
        };

        abort_unless($isAvailable, 404);

        return $next($request);
    }
}
