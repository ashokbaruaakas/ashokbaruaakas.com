<?php

declare(strict_types=1);

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(App\Settings\SettingsRepository::class)->save(new App\Settings\AuthenticationSettings()->fill(['allowRegistration' => true]));
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account', absolute: false));
});

test('registration is closed by default on a one-owner install', function () {
    app(App\Settings\SettingsRepository::class)->save(new App\Settings\AuthenticationSettings()->fill(['allowRegistration' => false]));

    $this->get('/register')->assertNotFound();
});
