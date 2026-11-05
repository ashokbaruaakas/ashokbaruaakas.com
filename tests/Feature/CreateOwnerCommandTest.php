<?php

declare(strict_types=1);

use App\Models\Profile;
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
        ->and($owner->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('correct-horse-battery', $owner->password))->toBeTrue();
});

test('it starts the public profile with the owner name and email', function (): void {
    $this->artisan('owner:create')
        ->expectsQuestion('Name', 'New Owner')
        ->expectsQuestion('Email', 'owner@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertSuccessful();

    $profile = Profile::query()->sole();

    expect($profile->name)->toBe('New Owner')
        ->and($profile->email)->toBe('owner@example.com');
});

test('it keeps a profile that already exists', function (): void {
    $profile = Profile::factory()->create(['name' => 'Existing Name']);

    $this->artisan('owner:create')
        ->expectsQuestion('Name', 'New Owner')
        ->expectsQuestion('Email', 'owner@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertSuccessful();

    expect(Profile::query()->sole()->is($profile))->toBeTrue()
        ->and($profile->refresh()->name)->toBe('Existing Name');
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

    expect(User::query()->exists())->toBeFalse()
        ->and(Profile::query()->exists())->toBeFalse();
});
