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
        ->toBe('Senior Full-Stack Engineer — Fintech & Business Platforms')
        ->and($resumePortfolio['professionalSummary'])
        ->toStartWith('Senior full-stack engineer (Laravel, PHP 8, Vue 3, TypeScript) with 8+ years')
        ->and($resumePortfolio['familiarSkills'])->toBe(['React (with Inertia)', 'Next.js', 'Go', 'Rust']);

    $response->assertSee('Familiar with: React (with Inertia), Next.js, Go, Rust')
        ->assertSee('HSM-backed saved cards')
        ->assertSee('40,000+ customers')
        ->assertSee('Full lifecycle: Own system design');
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
        ->assertSee('LinkedIn: https://www.linkedin.com/in/ashokbaruaakas')
        ->assertSee('Website: https://ashokbaruaakas.com')
        ->assertSee('Professional Summary')
        ->assertSee('Fintech and payments: Core payments engineer and later team lead of 6 engineers on SiPay')
        ->assertSee('Technical Skills')
        ->assertSee('Experience')
        ->assertSee('Senior Full-Stack Engineer — Grow More Gaze | Oct 2023 — Present')
        ->assertSee('Senior Software Engineer — Softrobotics Bangladesh Ltd | Sep 2021 — Sep 2023')
        ->assertSee('Full-Stack Web Developer & Designer — Stellar BD Ltd | Oct 2019 — Aug 2021', false)
        ->assertSee('Full-Stack Web Developer — Multiplex Web Design | Mar 2018 — Sep 2019')
        ->assertSee('Additional Projects')
        ->assertSee('Open Source')
        ->assertSee('Education')
        ->assertSee('BSc in Computer Science (evening program, while working full-time) — East Delta University - Chattogram, Bangladesh | 2018 — 2021')
        ->assertSee('Diploma in Computer Science — Bangladesh Sweden Polytechnic Institute - Kaptai, Rangamati, Bangladesh | 2013 — 2017')
        ->assertSee('Languages');
});

// The static PDF is a deploy-time build artifact (scripts/build-resume-pdf.mjs,
// rendered by the deploy workflow and gitignored), so its on-disk presence is
// verified by the deploy pipeline, not here. This test only asserts the
// application behaviour: the download link renders without the window.print hack.
test('the resume page renders a download link to the static resume pdf', function (): void {
    $this->get(route('resume'))
        ->assertOk()
        ->assertSee('href="/Ashok-Barua-Resume.pdf"', false)
        ->assertDontSee('window.print', false);
});

test('the resume page includes ProfilePage structured data', function (): void {
    $response = $this->get(route('resume'))->assertOk();

    expect(preg_match(
        '/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s',
        $response->getContent(),
        $matches,
    ))->toBe(1);

    $graph = collect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph']);

    expect($graph->firstWhere('@type', 'ProfilePage'))->toMatchArray([
        'url' => route('resume'),
        'mainEntity' => ['@id' => rtrim(route('home'), '/').'/#person'],
    ])
        ->and($graph->firstWhere('@type', 'Person')['name'])->toBe('Ashok Barua');
});

test('the resume separates company and date for text extraction', function (): void {
    $this->get(route('resume'))
        ->assertOk()
        ->assertSee('<span class="resume-date-sep"> | </span>', false);
});
