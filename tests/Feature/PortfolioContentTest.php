<?php

declare(strict_types=1);

use App\Enums\MediaKey;
use App\Models\CaseStudy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Media;
use App\Models\OpenSourceContribution;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\Testimonial;
use Database\Seeders\AshokBaruaSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedOwner();
    renderWithSsr();
});

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

    $this->seed(AshokBaruaSeeder::class);

    expect(Profile::query()->sole()->tagline)->toBe('Edited tagline')
        ->and(Testimonial::query()->count())->toBe(4);
});

test('the seeder stores every owner file under a content-hashed name', function (): void {
    expect(Media::query()->count())->toBe(count(MediaKey::cases()) - 1);

    foreach (Media::query()->get() as $media) {
        Storage::disk('public')->assertExists($media->path);

        expect($media->path)->toMatch("/^owner\\/{$media->key->value}-[a-f0-9]{16}\\.[a-z]+$/");
    }
});

test('public pages link to the stored owner files', function (): void {
    $response = $this->get(route('home'));

    foreach (Media::query()->whereIn('key', [MediaKey::Avatar, MediaKey::Portrait640, MediaKey::FaviconIco])->get() as $media) {
        $response->assertSee($media->url(), false);
    }
});

test('a portfolio built by another owner contains none of the seeded owner details', function (): void {
    foreach ([Media::class, Profile::class, PortfolioSection::class, PageContent::class, ProfileHighlight::class, Project::class, Experience::class, Education::class, Language::class, SkillCategory::class, SocialLink::class, OpenSourceContribution::class, CaseStudy::class, Testimonial::class] as $model) {
        $model::query()->delete();
    }

    Profile::factory()->create(['name' => 'Another Owner', 'professional_summary' => 'A summary.']);
    Experience::factory()->create();
    Project::factory()->create();
    OpenSourceContribution::factory()->create();

    foreach (['home', 'resume', 'resume.plain', 'open-source'] as $routeName) {
        $content = str_replace(
            [base_path(), parse_url(config('app.url'), PHP_URL_HOST), 'Ashok-Barua-Resume.pdf'],
            '',
            $this->get(route($routeName))->assertOk()->getContent(),
        );

        expect($content)->toContain('Another Owner')
            ->not->toContain('Ashok')
            ->not->toContain('ashokbaruaakas')
            ->not->toContain('SiPay')
            ->not->toContain('Grow More Gaze')
            ->not->toContain('Chattogram')
            ->not->toContain('UTC+6');
    }
});
