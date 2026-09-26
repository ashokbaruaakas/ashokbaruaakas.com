<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the resume page', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index')
            ->has('portfolio'));
});

test('the resume page renders the expected name', function () {
    $response = $this->get(route('resume'));

    $response->assertOk();
    $response->assertSee('Ashok Barua Akas');
});

test('resume and home share the same portfolio data and positioning', function () {
    $homePortfolio = $this->get(route('home'))->inertiaProps('portfolio');
    $response = $this->get(route('resume'));
    $resumePortfolio = $response->inertiaProps('portfolio');

    expect($resumePortfolio)->toEqual($homePortfolio)
        ->and($resumePortfolio['tagline'])
        ->toBe('Senior Full-Stack Engineer — Fintech & Multi-Tenant SaaS')
        ->and($resumePortfolio['professionalSummary'])
        ->toStartWith('Fintech and payments: Led development of SiPay')
        ->and($resumePortfolio['familiarSkills'])->toBe(['Go', 'Rust']);

    $response->assertSee('Familiar with: Go, Rust')
        ->assertSee('HSM-backed cryptographic operations')
        ->assertSee('40,000+ customers')
        ->assertSee('Full lifecycle: Own architecture');
});

test('the resume page shares the public layout with a footer', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index')
            ->has('portfolio', fn (Assert $portfolio) => $portfolio
                ->where('name', 'Ashok Barua Akas')
                ->where('location', 'Chattogram, Bangladesh')
                ->etc()));
});

test('the resume page shows the portfolio website link', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index')
            ->has('portfolio'));
});

test('the resume page references open-source work', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index')
            ->has('portfolio.openSourceContributions', 7));
});
