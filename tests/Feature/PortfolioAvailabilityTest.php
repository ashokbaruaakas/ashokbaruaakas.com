<?php

use App\Models\Experience;
use App\Models\OpenSourceContribution;
use App\Models\Profile;
use Inertia\Testing\AssertableInertia as Assert;

test('an empty install shows an uncached welcome page that search engines skip', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));

    expect($response->headers->get('Cache-Control'))->not->toContain('public');
});

test('an empty install hides every other public page', function (string $routeName): void {
    $response = $this->get(route($routeName));

    $response->assertNotFound();

    expect($response->headers->get('Cache-Control'))->not->toContain('public');
})->with([
    'resume' => 'resume',
    'plain resume' => 'resume.plain',
    'open source' => 'open-source',
]);

test('an empty install lists only the home page in the sitemap', function (): void {
    $sitemap = simplexml_load_string($this->get(route('sitemap'))->getContent());

    expect(count($sitemap->url))->toBe(1);
});

test('a profile with only a name shows the home page without optional sections or links', function (): void {
    Profile::factory()->nameOnly()->create(['name' => 'New Owner']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('portfolio.name', 'New Owner')
            ->where('portfolio.hasResume', false)
            ->where('portfolio.hasOpenSource', false)
            ->where('portfolio.visibleSections', ['hero']));
});

test('sections become visible once they have data', function (): void {
    Profile::factory()->nameOnly()->create();
    Experience::factory()->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('portfolio.visibleSections', ['hero', 'experience']));
});

test('the resume appears once it has a summary or experience', function (array $profileAttributes, bool $hasExperience): void {
    Profile::factory()->nameOnly()->create($profileAttributes);

    if ($hasExperience) {
        Experience::factory()->create();
    }

    $this->get(route('resume'))->assertOk();
    $this->get(route('resume.plain'))->assertOk();
})->with([
    'summary' => [['professional_summary' => 'A summary.'], false],
    'experience' => [[], true],
]);

test('the resume stays hidden with only a name', function (): void {
    Profile::factory()->nameOnly()->create();

    $this->get(route('resume'))->assertNotFound();
});

test('the open source page appears once it has a contribution', function (): void {
    Profile::factory()->nameOnly()->create();

    $this->get(route('open-source'))->assertNotFound();

    OpenSourceContribution::factory()->create();

    $this->get(route('open-source'))->assertOk();
});

test('the plain resume leaves out groups without data', function (): void {
    Profile::factory()->nameOnly()->create(['name' => 'New Owner', 'professional_summary' => 'A summary.']);

    expect($this->get(route('resume.plain'))->getContent())
        ->toBe("New Owner\n\n\nContact Information\n\nProfessional Summary\nA summary.\n");
});

test('the sitemap lists pages as they become available', function (): void {
    Profile::factory()->nameOnly()->create();
    OpenSourceContribution::factory()->create();

    $sitemap = simplexml_load_string($this->get(route('sitemap'))->getContent());
    $locations = array_map('strval', $sitemap->xpath('//*[local-name()="loc"]'));

    expect($locations)->toContain(route('open-source'))->not->toContain(route('resume'));
});
