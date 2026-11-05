<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\AuthenticationSettings;
use App\Settings\SettingsRepository;

function saveAuthenticationSettings(array $values): void
{
    app(SettingsRepository::class)->save(new AuthenticationSettings()->fill($values));
}

test('the owner can view the authentication settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('settings.authentication.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Authentication')
            ->where('settings', ['requireEmailVerification' => true]));
});

test('the owner can update the authentication settings', function (array $input, array $expected) {
    $this->actingAs(User::factory()->create())
        ->from(route('settings.authentication.edit'))
        ->put(route('settings.authentication.update'), $input)
        ->assertRedirect(route('settings.authentication.edit'))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Settings saved.']]);

    expect(app(AuthenticationSettings::class)->toArray())->toBe($expected);
})->with([
    'an unchecked box turns it off' => [[], ['requireEmailVerification' => false]],
    'a checked box turns it on' => [['requireEmailVerification' => '1'], ['requireEmailVerification' => true]],
]);

test('guests cannot manage the authentication settings', function () {
    $this->get(route('settings.authentication.edit'))->assertRedirect(route('login'));
    $this->put(route('settings.authentication.update'), [])->assertRedirect(route('login'));

    expect(app(AuthenticationSettings::class)->requireEmailVerification)->toBeTrue();
});

test('verified routes ask for a verified email while verification is required', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('verified routes let unverified users in while verification is not required', function () {
    saveAuthenticationSettings(['requireEmailVerification' => false]);

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('the profile only asks for verification while it is required', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('account.profile'))->assertInertia(fn ($page) => $page->where('mustVerifyEmail', true));

    saveAuthenticationSettings(['requireEmailVerification' => false]);

    $this->actingAs($user)->get(route('account.profile'))->assertInertia(fn ($page) => $page->where('mustVerifyEmail', false));
});
