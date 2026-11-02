<?php

use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Models\Testimonial;
use Database\Seeders\PortfolioSeeder;

test('public pages show content edited in the database', function (string $routeName): void {
    Profile::query()->firstOrFail()->update(['tagline' => 'Edited tagline']);

    expect($this->get(route($routeName))->inertiaProps('portfolio.tagline'))->toBe('Edited tagline');
})->with([
    'home' => 'home',
    'resume' => 'resume',
    'open source' => 'open-source',
]);

test('the plain-text resume shows content edited in the database', function (): void {
    Profile::query()->firstOrFail()->update(['email' => 'edited@example.com']);

    $this->get(route('resume.plain'))->assertSee('Email: edited@example.com');
});

test('portfolio lists follow their sort order', function (): void {
    Testimonial::factory()->create(['author' => 'First Author', 'sort_order' => -1]);

    $authors = array_column($this->get(route('home'))->inertiaProps('portfolio.testimonials'), 'author');

    expect($authors[0])->toBe('First Author')->and($authors)->toHaveCount(5);
});

test('project links follow their sort order', function (): void {
    $project = Project::factory()->create();
    ProjectLink::factory()->for($project)->create(['label' => 'Second', 'sort_order' => 2]);
    ProjectLink::factory()->for($project)->create(['label' => 'First', 'sort_order' => 1]);

    $projects = collect($this->get(route('home'))->inertiaProps('portfolio.projects'));

    expect(array_column($projects->firstWhere('name', $project->name)['links'], 'label'))->toBe(['First', 'Second']);
});

test('re-running the seeder keeps edited content', function (): void {
    Profile::query()->firstOrFail()->update(['tagline' => 'Edited tagline']);

    $this->seed(PortfolioSeeder::class);

    expect(Profile::query()->sole()->tagline)->toBe('Edited tagline')
        ->and(Testimonial::query()->count())->toBe(4);
});

test('public pages return not found when no profile exists', function (): void {
    Profile::query()->delete();

    $this->get(route('home'))->assertNotFound();
});
