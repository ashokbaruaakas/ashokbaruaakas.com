<?php

declare(strict_types=1);

use App\Mail\TestEmail;
use App\Models\User;
use App\Settings\MailSettings;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

test('the owner can view the mail settings with their env defaults', function () {
    config(['mail.default' => 'smtp', 'mail.from.name' => 'Laravibe', 'mail.from.address' => 'hello@example.com']);

    $this->actingAs(User::factory()->create())
        ->get(route('settings.mail.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Mail')
            ->where('settings', ['fromName' => null, 'fromAddress' => null])
            ->where('defaults', ['fromName' => 'Laravibe', 'fromAddress' => 'hello@example.com'])
            ->where('mailer', 'smtp'));
});

test('the owner can change the sender', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('settings.mail.update'), ['fromName' => 'Acme', 'fromAddress' => 'team@acme.test'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Settings saved.']]);

    expect(app(MailSettings::class)->toArray())->toBe(['fromName' => 'Acme', 'fromAddress' => 'team@acme.test'])
        ->and(config('mail.from'))->toBe(['address' => 'team@acme.test', 'name' => 'Acme']);
});

test('the sender address must be an email address', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('settings.mail.update'), ['fromAddress' => 'not-an-email'])
        ->assertSessionHasErrors('fromAddress');

    expect(app(MailSettings::class)->fromAddress)->toBeNull();
});

test('guests cannot manage the mail settings', function () {
    Mail::fake();

    $this->get(route('settings.mail.edit'))->assertRedirect(route('login'));
    $this->put(route('settings.mail.update'), ['fromName' => 'Acme'])->assertRedirect(route('login'));
    $this->post(route('settings.mail.test.store'))->assertRedirect(route('login'));

    Mail::assertNothingSent();
});

test('the owner can send themselves a test email', function () {
    Mail::fake();
    $admin = User::factory()->create(['email' => 'admin@acme.test']);

    $this->actingAs($admin)
        ->post(route('settings.mail.test.store'))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Test email sent to admin@acme.test.']]);

    Mail::assertSent(TestEmail::class, fn (TestEmail $mail): bool => $mail->hasTo('admin@acme.test'));
});

test('a failed test email is reported to the administrator', function () {
    $this->mock(Mailer::class, fn ($mailer) => $mailer->shouldReceive('to->send')->andThrow(new TransportException('Connection refused.')));

    $this->actingAs(User::factory()->create())
        ->post(route('settings.mail.test.store'))
        ->assertInertiaFlash('toasts', [[
            'type' => 'error',
            'message' => 'The test email could not be sent.',
            'description' => 'Connection refused.',
        ]]);
});

test('the test email names the application', function () {
    config(['app.name' => 'Acme']);

    new TestEmail()
        ->assertHasSubject('Test email from Acme')
        ->assertSeeInHtml('Your email is working');
});
