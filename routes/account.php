<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/account/profile', [AccountController::class, 'edit'])->name('account.profile');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');

    Route::get('/account/security', [SecurityController::class, 'edit'])
        ->middleware('password.confirm')
        ->name('account.security');
    Route::put('/account/security/password', [SecurityController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('account.security.password');
    Route::delete('/account/security/sessions', [SecurityController::class, 'destroyOtherSessions'])
        ->middleware('throttle:6,1')
        ->name('account.security.sessions.destroy');
});
