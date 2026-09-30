<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'seo' => [
                'canonicalUrl' => $this->canonicalUrl($request),
                'homeUrl' => $this->absoluteUrl('/'),
                'imageUrl' => asset('portfolio-og.png'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Build the canonical URL for the incoming request.
     *
     * The URL generator is forced to HTTPS, so the canonical URL stays correct
     * even when TLS is terminated at the edge proxy and the application only
     * ever sees plain HTTP.
     */
    protected function canonicalUrl(Request $request): string
    {
        return $this->absoluteUrl($request->getPathInfo());
    }

    /**
     * Generate an absolute URL for the given request path, keeping the
     * trailing slash on the root URL so it matches the sitemap.
     */
    protected function absoluteUrl(string $path): string
    {
        return $path === '/' ? url('/').'/' : url($path);
    }
}
