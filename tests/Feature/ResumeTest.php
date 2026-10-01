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
    $response->assertSee('Ashok Barua');
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
                ->where('name', 'Ashok Barua')
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

test('the resume page renders the download pdf icon button with a tooltip', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertSee('aria-label="Download PDF"', false)
        ->assertSee('data-slot="tooltip-trigger"', false)
        ->assertSee('rounded-full', false);
});

test('plain-text resume uses the shared portfolio data', function (): void {
    $response = $this->get(route('resume.plain'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Contact Information')
        ->assertSee('Email: ashokbaruaakas@gmail.com')
        ->assertSee('Phone: +8801829853914')
        ->assertSee('GitHub: https://github.com/ashokbaruaakas')
        ->assertSee('Website: https://ashokbaruaakas.com')
        ->assertSee('Professional Summary')
        ->assertSee('Fintech and payments: Led development of SiPay')
        ->assertSee('Technical Skills')
        ->assertSee('Experience')
        ->assertSee('Full Stack Developer — Grow More Gaze | Oct 2023 — Present')
        ->assertSee('Senior Software Engineer — Softrobotics Bangladesh Ltd | Sep 2021 — Sep 2023')
        ->assertSee('Full-stack Web Developer & Designer — Stellar BD Ltd | Oct 2019 — Aug 2021', false)
        ->assertSee('Full-stack Web Developer — Multiplex Web Design | Mar 2018 — Sep 2019')
        ->assertSee('Additional Projects')
        ->assertSee('Open Source')
        ->assertSee('Education')
        ->assertSee('BSc in Computer Science — East Delta University - Chittagong, Bangladesh | 2018 — 2021')
        ->assertSee('Diploma in Computer Science — Bangladesh Sweden Polytechnic Institute - Kaptai, Rangamati, Bangladesh | 2013 — 2017')
        ->assertSee('Languages');
});
