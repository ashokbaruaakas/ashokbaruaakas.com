<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;

test('public routes are reachable as a guest', function (string $routeName): void {
    $this->get(route($routeName))->assertOk();
})->with([
    'home' => 'home',
    'resume' => 'resume',
    'open source' => 'open-source',
]);

test('public routes are not protected by auth middleware', function (string $routeName): void {
    $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

    expect($middleware)
        ->not->toContain('auth')
        ->and($middleware)->not->toContain('auth:web')
        ->and($middleware)->not->toContain('verified');
})->with([
    'home' => 'home',
    'resume' => 'resume',
    'open source' => 'open-source',
]);

test('public routes perform no database queries', function (string $routeName): void {
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->get(route($routeName))->assertOk();

    expect(DB::getQueryLog())->toBeEmpty();
})->with([
    'home' => 'home',
    'resume' => 'resume',
    'open source' => 'open-source',
]);

test('public routes set no session cookies and are publicly cacheable', function (string $routeName): void {
    $response = $this->get(route($routeName))->assertOk();

    expect($response->headers->getCookies())->toBeEmpty()
        ->and($response->headers->get('Cache-Control'))->toContain('public', 'max-age=300');
})->with([
    'home' => 'home',
    'resume' => 'resume',
    'open source' => 'open-source',
]);

test('public routes send a nonce-based content security policy', function (): void {
    // The policy is skipped while the Vite dev server runs hot, so ignore a local public/hot file.
    Vite::useHotFile(storage_path('framework/testing/vite-not-running.hot'));

    $response = $this->get(route('home'))->assertOk();
    $policy = $response->headers->get('Content-Security-Policy');

    expect($policy)->toContain("default-src 'self'", "frame-ancestors 'none'", "object-src 'none'")
        ->and(preg_match("/script-src 'self' 'nonce-([^']+)'/", $policy, $matches))->toBe(1);

    $response->assertSee('nonce="'.$matches[1].'"', false);
});
