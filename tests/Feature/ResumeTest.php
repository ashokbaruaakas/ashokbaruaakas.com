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

test('the resume page shares the public layout with a footer', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index')
            ->has('portfolio', fn (Assert $portfolio) => $portfolio
                ->where('name', 'Ashok Barua Akas')
                ->where('location', 'Chittagong, Bangladesh')
                ->etc()));
});
