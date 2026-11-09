<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config(['mail.enabled' => false]);
});

test('password reset routes are not found while mail is off', function (string $method, string $uri) {
    Notification::fake();
    User::factory()->create(['email' => 'owner@example.com']);

    $this->call($method, $uri, ['email' => 'owner@example.com'])->assertNotFound();

    Notification::assertNothingSent();
})->with([
    'reset link screen' => ['GET', '/forgot-password'],
    'reset link request' => ['POST', '/forgot-password'],
    'reset screen' => ['GET', '/reset-password/token'],
    'reset' => ['POST', '/reset-password'],
]);

test('email verification routes are not found while mail is off', function (string $method, string $uri) {
    Notification::fake();

    $this->actingAs(User::factory()->unverified()->create())
        ->call($method, $uri)
        ->assertNotFound();

    Notification::assertNothingSent();
})->with([
    'notice' => ['GET', '/email/verify'],
    'resend' => ['POST', '/email/verification-notification'],
]);

test('the login page hides the password reset link while mail is off', function () {
    User::factory()->create();

    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('canResetPassword', false));
});

test('verified routes let unverified owners in while mail is off', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('changing the email address keeps it verified and sends nothing while mail is off', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('account.update'), ['name' => $user->name, 'email' => 'new@example.com'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->not->toBeNull();
    Notification::assertNothingSent();
});

test('the settings pages report that mail is off', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('settings.mail.edit'))->assertInertia(fn (Assert $page) => $page->where('enabled', false));
    $this->get(route('settings.authentication.edit'))->assertInertia(fn (Assert $page) => $page->where('mailEnabled', false));
});

test('a test email is refused while mail is off', function () {
    Mail::fake();

    $this->actingAs(User::factory()->create())
        ->post(route('settings.mail.test.store'))
        ->assertInertiaFlash('toasts', [['type' => 'error', 'message' => 'Mail is off. Set MAIL_MAILER in .env to turn it on.']]);

    Mail::assertNothingSent();
});
