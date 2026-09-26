<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
