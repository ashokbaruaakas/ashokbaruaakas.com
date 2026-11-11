<?php

declare(strict_types=1);

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Actions\Portfolio\GetPortfolioLastModifiedAction;
use App\Http\Controllers\Assistant\AssistantController;
use App\Http\Controllers\Assistant\AssistantMessageController;
use App\Http\Controllers\Assistant\AssistantProposalController;
use App\Http\Controllers\Assistant\CoverLetterController;
use App\Http\Controllers\Ats\AtsCheckController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobHunt\JobApplicationChatController;
use App\Http\Controllers\JobHunt\JobApplicationController;
use App\Http\Controllers\JobHunt\JobApplicationNoteController;
use App\Http\Controllers\JobHunt\JobApplicationSentResumeController;
use App\Http\Controllers\JobHunt\JobApplicationStatusController;
use App\Http\Controllers\JobHunt\JobHuntController;
use App\Http\Controllers\JobHunt\JobHuntEndController;
use App\Http\Controllers\JobHunt\JobPostReaderController;
use App\Http\Controllers\Portfolio\HomeController;
use App\Http\Controllers\Portfolio\MediaController;
use App\Http\Controllers\Portfolio\OpenSourceController;
use App\Http\Controllers\Portfolio\PlainTextResumeController;
use App\Http\Controllers\Portfolio\PortfolioCopyController;
use App\Http\Controllers\Portfolio\PortfolioListController;
use App\Http\Controllers\Portfolio\PortfolioListOrderController;
use App\Http\Controllers\Portfolio\PortfolioListVisibilityController;
use App\Http\Controllers\Portfolio\ProfileController;
use App\Http\Controllers\Portfolio\ResumeController;
use App\Http\Controllers\Portfolio\ResumePdfController;
use App\Http\Controllers\Portfolio\ResumePrintController;
use App\Http\Controllers\Portfolio\ResumeSettingsController;
use App\Http\Controllers\Settings\AiConnectionCheckController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\Settings\AuthenticationSettingsController;
use App\Http\Controllers\Settings\BackupController;
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

Route::get('/resumes/{resume:slug}/print', [ResumePrintController::class, 'show'])
    ->middleware('signed:relative')
    ->name('resumes.print');

Route::get('.well-known/passkey-endpoints', fn (): JsonResponse => response()->json([
    'enroll' => route('account.security'),
    'manage' => route('account.security'),
]))->name('well-known.passkeys');

Route::get('/robots.txt', fn (): Response => response("User-agent: *\nDisallow:\n\nSitemap: " . route('sitemap') . "\n")
    ->header('Content-Type', 'text/plain; charset=UTF-8'))->name('robots');

Route::get('/sitemap.xml', function (GetPortfolioAvailabilityAction $getPortfolioAvailability, GetPortfolioLastModifiedAction $getPortfolioLastModified): Response {
    $availability = $getPortfolioAvailability->handle();
    $lastModified = $getPortfolioLastModified->handle()?->toDateString();
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
        $xml .= '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . ($date === null ? '' : "<lastmod>{$date}</lastmod>") . '</url>';
    }

    $xml .= '</urlset>';

    return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::post('/ats-checks', [AtsCheckController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('ats-checks.store');

    Route::prefix('/portfolio')->as('portfolio.')
        ->group(function () {
            Route::redirect('/', '/portfolio/profile');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('/headings-and-seo', [PortfolioCopyController::class, 'edit'])->name('copy.edit');
            Route::put('/headings-and-seo', [PortfolioCopyController::class, 'update'])->name('copy.update');
            Route::get('/resume', [ResumeSettingsController::class, 'editPublic'])->name('resume.edit');
            Route::get('/resumes', [ResumeSettingsController::class, 'index'])->name('resumes.index');
            Route::post('/resumes', [ResumeSettingsController::class, 'store'])->name('resumes.store');
            Route::get('/resumes/{resume}', [ResumeSettingsController::class, 'edit'])->whereNumber('resume')->name('resumes.edit');
            Route::put('/resumes/{resume}', [ResumeSettingsController::class, 'update'])->whereNumber('resume')->name('resumes.update');
            Route::delete('/resumes/{resume}', [ResumeSettingsController::class, 'destroy'])->whereNumber('resume')->name('resumes.destroy');
            Route::post('/resumes/{resume}/public', [ResumeSettingsController::class, 'publish'])->whereNumber('resume')->name('resumes.publish');
            Route::post('/resumes/{resume}/rebuild', [ResumeSettingsController::class, 'rebuild'])->whereNumber('resume')->name('resumes.rebuild');
            Route::get('/resumes/{resume}/preview/{template}', [ResumeSettingsController::class, 'preview'])->whereNumber('resume')->name('resumes.preview');
            Route::get('/resume-builds/{build}/download', [ResumePdfController::class, 'download'])->whereNumber('build')->name('resume-builds.download');
            Route::post('/resume-pdf/rebuild', [ResumePdfController::class, 'store'])->name('resume-pdf.rebuild');
            Route::get('/images', [MediaController::class, 'index'])->name('media.index');
            Route::post('/images/{slot}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/images/{slot}', [MediaController::class, 'destroy'])->name('media.destroy');
            Route::get('/{list}', [PortfolioListController::class, 'index'])->name('lists.index');
            Route::post('/{list}', [PortfolioListController::class, 'store'])->name('lists.store');
            Route::put('/{list}/order', [PortfolioListOrderController::class, 'update'])->name('lists.order.update');
            Route::put('/{list}/{item}', [PortfolioListController::class, 'update'])->whereNumber('item')->name('lists.update');
            Route::delete('/{list}/{item}', [PortfolioListController::class, 'destroy'])->whereNumber('item')->name('lists.destroy');
            Route::put('/{list}/{item}/visibility', [PortfolioListVisibilityController::class, 'update'])->whereNumber('item')->name('lists.visibility.update');
        });

    Route::prefix('/job-hunt')->as('job-hunt.')->scopeBindings()
        ->group(function () {
            Route::get('/', [JobHuntController::class, 'index'])->name('index');
            Route::post('/', [JobHuntController::class, 'store'])->name('store');
            Route::post('/read-post', [JobPostReaderController::class, 'store'])
                ->middleware('throttle:20,1')
                ->name('read-post');
            Route::get('/{jobHunt}', [JobHuntController::class, 'show'])->name('show');
            Route::put('/{jobHunt}', [JobHuntController::class, 'update'])->name('update');
            Route::delete('/{jobHunt}', [JobHuntController::class, 'destroy'])->name('destroy');
            Route::post('/{jobHunt}/end', [JobHuntEndController::class, 'store'])->name('end');
            Route::post('/{jobHunt}/applications', [JobApplicationController::class, 'store'])->name('applications.store');
            Route::put('/{jobHunt}/applications/{application}', [JobApplicationController::class, 'update'])->name('applications.update');
            Route::delete('/{jobHunt}/applications/{application}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');
            Route::get('/{jobHunt}/applications/{application}/chat', [JobApplicationChatController::class, 'show'])->name('applications.chat');
            Route::put('/{jobHunt}/applications/{application}/status', [JobApplicationStatusController::class, 'update'])->name('applications.status.update');
            Route::put('/{jobHunt}/applications/{application}/sent-resume', [JobApplicationSentResumeController::class, 'update'])->name('applications.sent-resume.update');
            Route::post('/{jobHunt}/applications/{application}/notes', [JobApplicationNoteController::class, 'store'])->name('applications.notes.store');
            Route::delete('/{jobHunt}/applications/{application}/notes/{event}', [JobApplicationNoteController::class, 'destroy'])->name('applications.notes.destroy');
        });

    Route::prefix('/assistant')->as('assistant.')
        ->group(function () {
            Route::post('/messages', [AssistantMessageController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('messages.store');
            Route::post('/proposals/{proposal}/apply', [AssistantProposalController::class, 'apply'])->whereNumber('proposal')->name('proposals.apply');
            Route::post('/proposals/{proposal}/undo', [AssistantProposalController::class, 'undo'])->whereNumber('proposal')->name('proposals.undo');
            Route::post('/proposals/{proposal}/dismiss', [AssistantProposalController::class, 'dismiss'])->whereNumber('proposal')->name('proposals.dismiss');
            Route::get('/cover-letters/{coverLetter}.pdf', [CoverLetterController::class, 'show'])->whereNumber('coverLetter')->name('cover-letters.show');
            Route::get('/{conversation?}', [AssistantController::class, 'show'])->whereUuid('conversation')->name('show');
            Route::put('/{conversation}', [AssistantController::class, 'update'])->whereUuid('conversation')->name('update');
            Route::delete('/{conversation}', [AssistantController::class, 'destroy'])->whereUuid('conversation')->name('destroy');
        });

    Route::prefix('/settings')->as('settings.')
        ->group(function () {
            Route::redirect('/', '/settings/ai');
            Route::get('/authentication', [AuthenticationSettingsController::class, 'edit'])->name('authentication.edit');
            Route::put('/authentication', [AuthenticationSettingsController::class, 'update'])->name('authentication.update');
            Route::get('/mail', [MailSettingsController::class, 'edit'])->name('mail.edit');
            Route::put('/mail', [MailSettingsController::class, 'update'])->name('mail.update');
            Route::post('/mail/test', [TestEmailController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('mail.test.store');
            Route::get('/ai', [AiSettingsController::class, 'edit'])->name('ai.edit');
            Route::put('/ai', [AiSettingsController::class, 'update'])->name('ai.update');
            Route::post('/ai/check', [AiConnectionCheckController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('ai.check.store');
            Route::get('/backup', [BackupController::class, 'show'])->name('backup.show');
            Route::get('/backup/download', [BackupController::class, 'download'])->name('backup.download');
            Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
        });
});

require __DIR__ . '/account.php';
