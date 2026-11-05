<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\SettingsRepository;

test('the owner can view the general settings', function () {
    app(SettingsRepository::class)->save(new GeneralSettings()->fill(['tagline' => 'Build faster']));
    $appName = config('app.name');

    $this->actingAs(User::factory()->create())
        ->get(route('settings.general.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/General')
            ->where('settings', ['siteName' => null, 'tagline' => 'Build faster', 'supportEmail' => null])
            ->where('defaultSiteName', $appName));
});

test('the settings section opens on the general settings', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings')
        ->assertRedirect('/settings/general');
});

test('the owner can update the general settings', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('settings.general.edit'))
        ->put(route('settings.general.update'), [
            'siteName' => 'Acme',
            'tagline' => 'Build faster',
            'supportEmail' => 'help@acme.test',
        ])
        ->assertRedirect(route('settings.general.edit'))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Settings saved.']]);

    app(SettingsRepository::class)->forget();

    expect(app(SettingsRepository::class)->load(GeneralSettings::class)->toArray())->toBe([
        'siteName' => 'Acme',
        'tagline' => 'Build faster',
        'supportEmail' => 'help@acme.test',
    ]);
});

test('clearing a general setting restores its default', function () {
    $appName = config('app.name');
    app(SettingsRepository::class)->save(new GeneralSettings()->fill(['siteName' => 'Acme', 'tagline' => 'Build faster']));

    $this->actingAs(User::factory()->create())
        ->put(route('settings.general.update'), ['siteName' => '', 'tagline' => 'Build faster', 'supportEmail' => ''])
        ->assertSessionHasNoErrors();

    expect(app(GeneralSettings::class)->siteName)->toBeNull()
        ->and(config('app.name'))->toBe($appName);
});

test('the general settings are validated', function (array $input, string $field) {
    $this->actingAs(User::factory()->create())
        ->put(route('settings.general.update'), $input)
        ->assertSessionHasErrors($field);

    expect(app(GeneralSettings::class)->toArray())->toBe(new GeneralSettings()->toArray());
})->with([
    'site name too long' => [['siteName' => str_repeat('a', 61)], 'siteName'],
    'tagline too long' => [['tagline' => str_repeat('a', 161)], 'tagline'],
    'invalid support email' => [['supportEmail' => 'not-an-email'], 'supportEmail'],
]);

test('guests cannot manage the settings', function () {
    $this->get(route('settings.general.edit'))->assertRedirect(route('login'));
    $this->put(route('settings.general.update'), ['siteName' => 'Acme'])->assertRedirect(route('login'));

    expect(app(GeneralSettings::class)->siteName)->toBeNull();
});

test('guests are sent to log in', function () {
    $this->get(route('settings.general.edit'))->assertRedirect(route('login'));
});

test('the tagline is the meta description', function () {
    $this->get(route('home'))->assertDontSee('<meta name="description"', false);

    app(SettingsRepository::class)->save(new GeneralSettings()->fill(['tagline' => 'Build faster & ship']));

    $this->get(route('home'))->assertSee('<meta name="description" content="Build faster &amp; ship">', false);
});

test('error pages show the support email', function () {
    $this->get('/missing')->assertInertia(fn ($page) => $page->where('supportEmail', null));

    app(SettingsRepository::class)->save(new GeneralSettings()->fill(['supportEmail' => 'help@acme.test']));

    $this->get('/missing')->assertInertia(fn ($page) => $page->where('supportEmail', 'help@acme.test'));
});
