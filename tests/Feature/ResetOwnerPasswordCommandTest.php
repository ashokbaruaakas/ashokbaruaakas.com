<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it sets a new password for the owner', function (): void {
    $owner = User::factory()->create(['remember_token' => 'old-token']);

    $this->artisan('owner:reset-password')
        ->expectsQuestion('New password', 'correct-horse-battery')
        ->expectsQuestion('Confirm new password', 'correct-horse-battery')
        ->assertSuccessful();

    $owner->refresh();

    expect(Hash::check('correct-horse-battery', $owner->password))->toBeTrue()
        ->and($owner->remember_token)->not->toBe('old-token');
});

test('it keeps the old password when the new one is invalid', function (): void {
    $owner = User::factory()->create();
    $password = $owner->password;

    $this->artisan('owner:reset-password')
        ->expectsQuestion('New password', 'correct-horse-battery')
        ->expectsQuestion('Confirm new password', 'different')
        ->assertFailed();

    expect($owner->refresh()->password)->toBe($password);
});

test('it fails when no owner account exists', function (): void {
    $this->artisan('owner:reset-password')->assertFailed();
});
