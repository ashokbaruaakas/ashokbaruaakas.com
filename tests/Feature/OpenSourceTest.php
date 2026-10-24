<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the open-source page', function () {
    $this->get(route('open-source'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('open-source/Index')
            ->has('portfolio.openSourceContributions', 8));
});

test('open-source contributions are ordered newest first and tagged', function () {
    $response = $this->get(route('open-source'));
    $contributions = $response->inertiaProps('portfolio.openSourceContributions');

    expect(array_column($contributions, 'title'))->toBe([
        'Notify Fanout',
        'LaraVibe Standards',
        'clawkit',
        'LaraVibe-Vue',
        'Inertia Laravel: inertiaProps',
        'The SoftPulze',
        'DevPulse CLI',
        'Minor OSS contributions',
    ])
        ->and($contributions[0])->toMatchArray([
            'date' => 'October 17, 2026',
            'secondaryUrl' => 'https://github.com/marketplace/actions/notify-fanout',
        ])
        ->and($contributions[2]['metric'])->toBe('500+ image pulls')
        ->and($contributions[4]['tags'])->toContain('Laravel', 'Developer Tools');
});
