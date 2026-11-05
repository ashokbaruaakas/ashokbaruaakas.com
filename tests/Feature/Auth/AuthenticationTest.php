<?php

declare(strict_types=1);

use App\Listeners\SyncSignedInCookie;
use App\Models\User;
use Illuminate\Support\Facades\Date;

test('login screen redirects home until an owner account exists', function () {
    $this->get('/login')->assertRedirect(route('home'));
});

test('login screen can be rendered', function () {
    User::factory()->create();

    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('user login is rate limited after multiple failed attempts', function () {
    Date::setTestNow(now());
    $user = User::factory()->create();

    $uri = '/login';
    $data = [
        'email' => $user->email,
        'password' => 'wrong-password',
    ];

    foreach (range(1, 5) as $attempt) {
        $this->post($uri, $data)
            ->assertInvalid('email');
    }
    $response = $this->post($uri, $data);

    $response->assertInvalid(['email' => trans('auth.throttle', ['seconds' => 60])]);
});

test('logging in sets a readable signed-in cookie for the public dashboard link', function () {
    $user = User::factory()->create();

    $cookie = $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertCookieNotExpired(SyncSignedInCookie::NAME)
        ->getCookie(SyncSignedInCookie::NAME, decrypt: false);

    expect($cookie->getValue())->toBe('1')
        ->and($cookie->isHttpOnly())->toBeFalse();
});

test('a failed login does not set the signed-in cookie', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertCookieMissing(SyncSignedInCookie::NAME);
});

test('logging out expires the signed-in cookie', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertCookieExpired(SyncSignedInCookie::NAME);
});

test('the signed-in cookie grants no access to the dashboard', function () {
    $this->withUnencryptedCookie(SyncSignedInCookie::NAME, '1')
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
