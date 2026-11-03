<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates the owner account', function (): void {
    $this->artisan('owner:create')
        ->expectsQuestion('Name', 'New Owner')
        ->expectsQuestion('Email', 'owner@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertSuccessful();

    $owner = User::query()->sole();

    expect($owner->email)->toBe('owner@example.com')
        ->and($owner->is_admin)->toBeTrue()
        ->and($owner->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('correct-horse-battery', $owner->password))->toBeTrue();
});

test('it refuses to run when an account already exists', function (): void {
    User::factory()->create();

    $this->artisan('owner:create')->assertFailed();

    expect(User::query()->count())->toBe(1);
});

test('it rejects invalid details without creating an account', function (): void {
    $this->artisan('owner:create')
        ->expectsQuestion('Name', 'New Owner')
        ->expectsQuestion('Email', 'not-an-email')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'different')
        ->assertFailed();

    expect(User::query()->exists())->toBeFalse();
});
