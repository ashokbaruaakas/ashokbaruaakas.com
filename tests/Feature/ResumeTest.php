<?php

use Inertia\Testing\AssertableInertia as Assert;

test('guests can visit the resume page', function () {
    $this->get(route('resume'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('resume/Index'));
});

test('the resume page renders the expected name', function () {
    $response = $this->get(route('resume'));

    $response->assertOk();
    $response->assertSee('Ashok Barua Akas');
});
