<?php

declare(strict_types=1);

use App\Models\Experience;
use App\Models\OpenSourceContribution;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('an empty install shows an uncached welcome page that search engines skip', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('href="/favicon.svg"', false)
        ->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('hasOwner', false));

    expect($response->headers->get('Cache-Control'))->not->toContain('public');
});

test('the welcome page knows once an owner account exists', function (): void {
    User::factory()->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('hasOwner', true));
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

    expect(count($sitemap->url))->toBe(1)
        ->and(isset($sitemap->url->lastmod))->toBeFalse();
});

test('a profile with only a name and email keeps the welcome page', function (): void {
    Profile::factory()->nameOnly()->create(['email' => 'owner@example.com']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('a profile with any other detail shows the home page without optional sections or links', function (): void {
    Profile::factory()->nameOnly()->create(['name' => 'New Owner', 'headline' => 'Engineer']);

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

test('the sitemap dates pages by the latest content change', function (): void {
    Profile::factory()->create(['updated_at' => '2026-01-05 10:00:00']);
    Experience::factory()->create(['updated_at' => '2026-03-09 10:00:00']);
    OpenSourceContribution::factory()->create(['updated_at' => '2026-02-01 10:00:00']);

    $sitemap = simplexml_load_string($this->get(route('sitemap'))->getContent());
    $dates = array_map('strval', $sitemap->xpath('//*[local-name()="lastmod"]'));

    expect($dates)->toBe(['2026-03-09', '2026-03-09', '2026-03-09']);
});

test('sections and pages fall back to plain names until their copy is written', function (): void {
    Profile::factory()->nameOnly()->create(['name' => 'New Owner', 'headline' => 'Engineer']);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', 'New Owner')
            ->where('portfolio.sections.work.title', 'Work')
            ->where('portfolio.sections.work.description', null)
            ->where('portfolio.pages.home.title', 'New Owner')
            ->where('portfolio.pages.home.description', '')
            ->where('portfolio.pages.home.imageAlt', 'New Owner'));
});

test('section and page copy comes from the database', function (): void {
    Profile::factory()->nameOnly()->create();
    PortfolioSection::factory()->create(['key' => 'work', 'title' => 'Selected Work']);
    PageContent::factory()->create(['key' => 'home', 'title' => 'Custom Title']);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('portfolio.sections.work.title', 'Selected Work')
            ->where('portfolio.pages.home.title', 'Custom Title'));
});

test('profile highlights follow their sort order', function (): void {
    Profile::factory()->nameOnly()->create();
    ProfileHighlight::factory()->create(['label' => 'Second', 'sort_order' => 2]);
    ProfileHighlight::factory()->create(['label' => 'First', 'sort_order' => 1]);

    $highlights = $this->get(route('home'))->inertiaProps('portfolio.highlights');

    expect(array_column($highlights, 'label'))->toBe(['First', 'Second']);
});

test('the plain resume shows the timezone label only when one is set', function (?string $timezoneLabel, string $expectedLine): void {
    Profile::factory()->nameOnly()->create([
        'location' => 'Dhaka',
        'timezone_label' => $timezoneLabel,
        'professional_summary' => 'A summary.',
    ]);

    $this->get(route('resume.plain'))->assertSee($expectedLine, false);
})->with([
    'with label' => ['UTC+6', 'Location: Dhaka (UTC+6)'],
    'without label' => [null, "Location: Dhaka\n"],
]);
