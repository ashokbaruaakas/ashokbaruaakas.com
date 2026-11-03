<?php

declare(strict_types=1);

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Http\Controllers\Administration\DashboardController;
use App\Http\Controllers\Administration\Settings\AuthenticationSettingsController;
use App\Http\Controllers\Administration\Settings\GeneralSettingsController;
use App\Http\Controllers\Administration\Settings\MailSettingsController;
use App\Http\Controllers\Administration\Settings\TestEmailController;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\UserRoleController;
use App\Http\Controllers\Administration\UserVerificationController;
use App\Http\Controllers\Portfolio\HomeController;
use App\Http\Controllers\Portfolio\OpenSourceController;
use App\Http\Controllers\Portfolio\PlainTextResumeController;
use App\Http\Controllers\Portfolio\ResumeController;
use App\Http\Middleware\EnsurePortfolioPageIsAvailable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Public portfolio pages carry no user state: no session or XSRF cookies, and
// shared caches may store them briefly.
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
])->middleware([
    EnsurePortfolioPageIsAvailable::class,
    'cache.headers:public;max_age=300;etag',
])->group(function (): void {
    Route::get('/', [HomeController::class, 'show'])->name('home');
    Route::get('/resume', [ResumeController::class, 'show'])->name('resume');
    Route::get('/resume/plain', [PlainTextResumeController::class, 'show'])->name('resume.plain');
    Route::get('/open-source', [OpenSourceController::class, 'show'])->name('open-source');
});

Route::get('.well-known/passkey-endpoints', fn (): JsonResponse => response()->json([
    'enroll' => route('account.security'),
    'manage' => route('account.security'),
]))->name('well-known.passkeys');

Route::get('/robots.txt', fn (): Response => response("User-agent: *\nDisallow:\n\nSitemap: " . route('sitemap') . "\n")
    ->header('Content-Type', 'text/plain; charset=UTF-8'))->name('robots');

Route::get('/sitemap.xml', function (GetPortfolioAvailabilityAction $getPortfolioAvailability): Response {
    $availability = $getPortfolioAvailability->handle();
    $lastModified = now()->toDateString();
    $urls = [
        // The home URL keeps its trailing slash so the sitemap matches the
        // canonical URL rendered on the home page.
        mb_rtrim(route('home'), '/') . '/' => $lastModified,
    ];

    if ($availability->hasResume) {
        $urls[route('resume')] = $lastModified;
    }

    if ($availability->hasOpenSource) {
        $urls[route('open-source')] = $lastModified;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    foreach ($urls as $url => $date) {
        $xml .= '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . $date . '</lastmod></url>';
    }

    $xml .= '</urlset>';

    return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::middleware(['auth', 'verified', 'can:accessAdministration'])
    ->prefix('/administration')->as('administration.')
    ->group(function () {
        Route::redirect('/', 'administration/dashboard');
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('/users/{user}/role', [UserRoleController::class, 'update'])->name('users.role.update');
        Route::post('/users/{user}/verification-notification', [UserVerificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('users.verification.store');

        Route::middleware('can:manageSettings')
            ->prefix('/settings')->as('settings.')
            ->group(function () {
                Route::redirect('/', '/administration/settings/general');
                Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
                Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');
                Route::get('/authentication', [AuthenticationSettingsController::class, 'edit'])->name('authentication.edit');
                Route::put('/authentication', [AuthenticationSettingsController::class, 'update'])->name('authentication.update');
                Route::get('/mail', [MailSettingsController::class, 'edit'])->name('mail.edit');
                Route::put('/mail', [MailSettingsController::class, 'update'])->name('mail.update');
                Route::post('/mail/test', [TestEmailController::class, 'store'])
                    ->middleware('throttle:6,1')
                    ->name('mail.test.store');
            });
    });

require __DIR__ . '/account.php';
