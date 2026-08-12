<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the portfolio home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('portfolio'));
});

test('the portfolio page renders the expected identity', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('portfolio', fn (Assert $portfolio) => $portfolio
                ->where('name', 'Ashok Barua Akas')
                ->where('tagline', 'Full-Stack Engineer · Laravel · Vue · TypeScript · Go')
                ->where('location', 'Chittagong, Bangladesh')
                ->where('githubUsername', 'ashokbaruaakas')
                ->where('organization', 'softpulze')
                ->etc()));
});

test('the portfolio page exposes social links', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.socialLinks'));

    $socialLinks = $response->inertiaProps('portfolio.socialLinks');

    expect($socialLinks)->toHaveCount(5)
        ->and($socialLinks[0])->toMatchArray([
            'platform' => 'GitHub',
            'url' => 'https://github.com/ashokbaruaakas',
            'icon' => 'github',
        ]);
});

test('the portfolio page exposes five skill categories with items', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.skills', 5));

    foreach ($response->inertiaProps('portfolio.skills') as $category) {
        expect($category)->toHaveKeys(['category', 'items'])
            ->and($category['items'])->not->toBeEmpty();
    }
});

test('the portfolio page exposes featured projects', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.projects', 5));

    foreach ($response->inertiaProps('portfolio.projects') as $project) {
        expect($project)->toHaveKeys([
            'name',
            'description',
            'owner',
            'repo',
            'technologies',
            'stars',
            'language',
        ]);
    }
});

test('the portfolio page exposes experience entries', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('portfolio.experience', 2));

    expect($response->inertiaProps('portfolio.experience')[0])->toMatchArray([
        'company' => 'Grow More Gaze',
        'period' => 'Present',
    ]);
});
