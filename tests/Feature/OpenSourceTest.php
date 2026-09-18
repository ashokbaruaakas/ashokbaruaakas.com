<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the open-source page', function () {
    $this->get(route('open-source'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('open-source/Index')
            ->has('portfolio.openSourceContributions', 7));
});

test('open-source contributions are ordered newest first and tagged', function () {
    $response = $this->get(route('open-source'));
    $contributions = $response->inertiaProps('portfolio.openSourceContributions');

    expect($contributions[0])->toMatchArray([
        'title' => 'Inertia Laravel: inertiaProps',
        'date' => 'June 5, 2025',
    ])
        ->and($contributions[0]['tags'])->toContain('Laravel', 'Developer Tools')
        ->and($contributions[2])->toMatchArray([
            'title' => 'Clawkit',
            'metric' => '500+ image pulls',
        ])
        ->and($contributions[6])->toMatchArray([
            'title' => 'Minor OSS contributions',
            'organization' => 'Raycast Extensions',
        ]);
});
