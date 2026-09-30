<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OpenSourceController;
use App\Http\Controllers\PlainTextResumeController;
use App\Http\Controllers\ResumeController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/resume', ResumeController::class)->name('resume');
Route::get('/resume/plain', PlainTextResumeController::class)->name('resume.plain');
Route::get('/open-source', OpenSourceController::class)->name('open-source');
Route::get('/sitemap.xml', function (): Response {
    $lastModified = now()->toDateString();
    $urls = [
        // The home URL keeps its trailing slash so the sitemap matches the
        // canonical URL rendered on the home page.
        rtrim(route('home'), '/').'/' => $lastModified,
        route('resume') => $lastModified,
        route('open-source') => $lastModified,
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    foreach ($urls as $url => $date) {
        $xml .= '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc><lastmod>'.$date.'</lastmod></url>';
    }

    $xml .= '</urlset>';

    return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/Index')->name('dashboard');
});

require __DIR__.'/settings.php';
