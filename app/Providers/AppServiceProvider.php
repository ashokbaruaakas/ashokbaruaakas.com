<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\AssistantTurn;
use App\Ai\OpenCodeSession;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\LaraTweaks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Override;
use Psr\Http\Message\RequestInterface;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->scoped(OpenCodeSession::class);
        $this->app->scoped(AssistantTurn::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LaraTweaks::default();
        URL::forceScheme('https');

        $this->configureGates();
        $this->configureErrorPages();

        Http::globalRequestMiddleware(fn (RequestInterface $request): RequestInterface => resolve(OpenCodeSession::class)($request));
    }

    /**
     * Define the gates behind the abilities shared with the frontend through `auth.can`.
     */
    private function configureGates(): void
    {
        Gate::define('updateProfile', fn (User $user): bool => true);
        Gate::define('deleteAccount', fn (User $user): bool => true);
    }

    /**
     * Render HTTP errors as Inertia pages. Server errors keep Laravel's debug page while debugging,
     * and an expired form submission goes back to the form instead of showing the error at its URL.
     */
    private function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ExceptionResponse|RedirectResponse|null {
            $status = $response->statusCode();

            if ($response->request->expectsJson() || $response->request->is('api/*') || ! in_array($status, [403, 404, 419, 500, 503], true)) {
                return null;
            }

            if ($status === 419 && ! $response->request->isMethod('GET')) {
                toastWarning(__('The page expired. Please try again.'));

                return back();
            }

            if ($status >= 500 && config()->boolean('app.debug')) {
                return null;
            }

            return $response->render('Error', [
                'status' => $status,
                'supportEmail' => app(GeneralSettings::class)->supportEmail,
            ])->withSharedData();
        });
    }
}
