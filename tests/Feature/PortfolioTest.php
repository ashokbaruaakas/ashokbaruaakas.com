<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the portfolio home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('portfolio'));
});

test('the portfolio page renders the expected identity', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('portfolio', fn (Assert $portfolio) => $portfolio
                ->where('name', 'Ashok Barua')
                ->where('tagline', 'Senior Full-Stack Engineer — Fintech & Business Platforms')
                ->where('location', 'Chattogram, Bangladesh')
                ->where('githubUsername', 'ashokbaruaakas')
                ->where('organization', 'thesoftpulze')
                ->where('email', 'ashokbaruaakas@gmail.com')
                ->where('phone', '+8801829853914')
                ->etc()));
});

test('the portfolio page exposes social links', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.socialLinks'));

    $socialLinks = $response->inertiaProps('portfolio.socialLinks');

    expect($socialLinks)->toHaveCount(6)
        ->and($socialLinks[0])->toMatchArray([
            'platform' => 'LinkedIn',
            'url' => 'https://www.linkedin.com/in/ashokbaruaakas',
            'isPrimary' => true,
        ])
        ->and($socialLinks[1])->toMatchArray([
            'platform' => 'GitHub',
            'url' => 'https://github.com/ashokbaruaakas',
            'icon' => 'github',
            'isPrimary' => true,
        ])
        ->and(collect($socialLinks)->where('isPrimary', true)->pluck('platform')->all())->toBe(['LinkedIn', 'GitHub']);
});

test('the portfolio page exposes seven skill categories with items', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.skills', 7));

    foreach ($response->inertiaProps('portfolio.skills') as $category) {
        expect($category)->toHaveKeys(['category', 'items'])
            ->and($category['items'])->not->toBeEmpty();
    }

    expect($response->inertiaProps('portfolio.familiarSkills'))->toBe(['React (with Inertia)', 'Next.js', 'Go', 'Rust'])
        ->and($response->inertiaProps('portfolio.skills')[0]['items'])
        ->not->toContain('Go', 'Rust')
        ->and($response->inertiaProps('portfolio.skills')[1]['items'])
        ->not->toContain('React', 'Next.js');
});

test('the portfolio page exposes featured projects', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.projects', 6));

    $projects = $response->inertiaProps('portfolio.projects');

    foreach ($projects as $project) {
        expect($project)->toHaveKeys([
            'name',
            'description',
            'owner',
            'repo',
            'technologies',
            'stars',
            'language',
            'tier',
            'period',
            'role',
            'highlights',
            'metric',
            'isPublicRepo',
            'linkLabel',
            'secondaryUrl',
            'secondaryLabel',
        ]);
    }

    expect($projects[0])->toMatchArray([
        'name' => 'SiPay',
        'tier' => 1,
        'period' => '2021 — 2023',
        'demoUrl' => 'https://sipay.com.tr/en/',
        'linkLabel' => 'Product',
    ])
        ->and($projects[1])->toMatchArray([
            'name' => 'Grow More Gaze',
            'metric' => '40,000+ customers',
            'isPublicRepo' => false,
            'demoUrl' => 'https://growmoregaze.com',
        ])
        ->and($projects[1]['highlights'])->toHaveCount(4)
        ->and($projects[2])->toMatchArray([
            'name' => 'Stellar BD — HRM & Payroll System',
            'demoUrl' => 'https://stellarbd.com',
            'linkLabel' => 'Company',
        ])
        ->and($projects[3])->toMatchArray([
            'name' => 'bizztechsz.com',
            'tier' => 2,
            'isPublicRepo' => false,
        ])
        ->and($projects[4])->toMatchArray([
            'name' => 'LaraVibe-Vue',
            'isPublicRepo' => true,
            'stars' => 2,
        ])
        ->and($projects[5])->toMatchArray([
            'name' => 'clawkit',
            'isPublicRepo' => true,
            'stars' => 1,
            'secondaryUrl' => 'https://github.com/ashokbaruaakas/clawkit/pkgs/container/clawkit',
            'secondaryLabel' => 'GHCR',
        ]);
});

test('the portfolio page exposes open-source contributions', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('portfolio.openSourceContributions', 7));
});

test('the portfolio page exposes experience entries', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.experience', 4));

    expect($response->inertiaProps('portfolio.experience')[0])->toMatchArray([
        'company' => 'Grow More Gaze',
        'period' => 'Oct 2023 — Present',
    ]);
});

test('the portfolio page exposes education and languages', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.education', 2)
        ->has('portfolio.languages', 2));

    expect($response->inertiaProps('portfolio.education')[0])->toMatchArray([
        'degree' => 'BSc in Computer Science (evening program, while working full-time)',
        'school' => 'East Delta University - Chattogram, Bangladesh',
    ]);

    expect($response->inertiaProps('portfolio.languages')[0])->toMatchArray([
        'name' => 'Bengali',
        'level' => 'Native',
    ]);
});

test('the portfolio page exposes case studies and testimonials', function () {
    $response = $this->get(route('home'));

    expect($response->inertiaProps('portfolio.caseStudies'))->toHaveCount(2)
        ->each->toHaveKeys(['title', 'problem', 'constraints', 'design', 'tradeoffs', 'results'])
        ->and($response->inertiaProps('portfolio.testimonials'))->toHaveCount(4)
        ->each->toHaveKeys(['quote', 'author', 'role', 'company'])
        ->and(array_column($response->inertiaProps('portfolio.testimonials'), 'author'))
        ->toBe(['Md. Shehad', 'Rajib Deb', 'Md. Amanot Ali', 'Md. Arafat Uddin']);
});
