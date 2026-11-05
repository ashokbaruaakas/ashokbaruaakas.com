<?php

declare(strict_types=1);

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portfolio\HomeController;
use App\Http\Controllers\Portfolio\MediaController;
use App\Http\Controllers\Portfolio\OpenSourceController;
use App\Http\Controllers\Portfolio\PlainTextResumeController;
use App\Http\Controllers\Portfolio\PortfolioCopyController;
use App\Http\Controllers\Portfolio\PortfolioListController;
use App\Http\Controllers\Portfolio\PortfolioListOrderController;
use App\Http\Controllers\Portfolio\ProfileController;
use App\Http\Controllers\Portfolio\ResumeController;
use App\Http\Controllers\Portfolio\ResumePdfController;
use App\Http\Controllers\Settings\AuthenticationSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\MailSettingsController;
use App\Http\Controllers\Settings\TestEmailController;
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
    Route::get('/resume.pdf', [ResumePdfController::class, 'show'])->name('resume.pdf');
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

    Route::prefix('/portfolio')->as('portfolio.')
        ->group(function () {
            Route::redirect('/', '/portfolio/profile');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('/copy', [PortfolioCopyController::class, 'edit'])->name('copy.edit');
            Route::put('/copy', [PortfolioCopyController::class, 'update'])->name('copy.update');
            Route::post('/resume-pdf/rebuild', [ResumePdfController::class, 'store'])->name('resume-pdf.rebuild');
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media/{slot}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{slot}', [MediaController::class, 'destroy'])->name('media.destroy');
            Route::get('/{list}', [PortfolioListController::class, 'index'])->name('lists.index');
            Route::post('/{list}', [PortfolioListController::class, 'store'])->name('lists.store');
            Route::put('/{list}/order', [PortfolioListOrderController::class, 'update'])->name('lists.order.update');
            Route::put('/{list}/{item}', [PortfolioListController::class, 'update'])->whereNumber('item')->name('lists.update');
            Route::delete('/{list}/{item}', [PortfolioListController::class, 'destroy'])->whereNumber('item')->name('lists.destroy');
        });

    Route::prefix('/settings')->as('settings.')
        ->group(function () {
            Route::redirect('/', '/settings/general');
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
