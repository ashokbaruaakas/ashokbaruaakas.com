<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it seeds the owner account', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->sole()->email)->toBe('admin@example.com');
});

test('it seeds nothing in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0);
});
