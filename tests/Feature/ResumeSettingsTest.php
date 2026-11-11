<?php

declare(strict_types=1);

use App\Jobs\RebuildResumePdf;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Vite;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Bus::fake();
    $this->owner = User::factory()->create();
});

test('the owner sees the public resume settings and the template choices', function (): void {
    Resume::public()->update(['page_count' => 3]);

    $this->actingAs($this->owner)->get(route('portfolio.resume.edit'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('portfolio/Resume')
        ->where('resume.template', 'classic')
        ->where('resume.pageLimit', 2)
        ->where('resume.pageCount', 3)
        ->where('resume.isOverPageLimit', true)
        ->where('templates.0.value', 'classic')
        ->where('templates.0.isAtsSafe', true));
});

test('the owner can change the page limit without rebuilding the pdf', function (): void {
    $this->actingAs($this->owner)->put(route('portfolio.resume.update'), ['template' => 'classic', 'page_limit' => 1])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Resume::public()->page_limit)->toBe(1);
    Bus::assertNotDispatched(RebuildResumePdf::class);
});

test('the page limit and template must be valid', function (array $input, string $field): void {
    $this->actingAs($this->owner)->put(route('portfolio.resume.update'), [...['template' => 'classic', 'page_limit' => 2], ...$input])
        ->assertSessionHasErrors($field);

    expect(Resume::public()->page_limit)->toBe(2);
})->with([
    'no page limit' => [['page_limit' => null], 'page_limit'],
    'zero pages' => [['page_limit' => 0], 'page_limit'],
    'too many pages' => [['page_limit' => 6], 'page_limit'],
    'unknown template' => [['template' => 'fancy'], 'template'],
]);

test('guests cannot see or change the resume settings', function (): void {
    $this->get(route('portfolio.resume.edit'))->assertRedirect(route('login'));
    $this->put(route('portfolio.resume.update'), ['template' => 'classic', 'page_limit' => 1])->assertRedirect(route('login'));

    expect(Resume::public()->page_limit)->toBe(2);
});

test('the owner can preview each template with their content', function (string $template): void {
    Profile::factory()->create();

    $this->actingAs($this->owner)->get(route('portfolio.resume.preview', $template))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('resume/Preview')
        ->where('template', $template)
        ->has('portfolio'));
})->with(['classic', 'compact']);

test('an unknown template has no preview', function (): void {
    $this->actingAs($this->owner)->get('/portfolio/resume/preview/fancy')->assertNotFound();
});

test('guests cannot preview templates', function (): void {
    $this->get(route('portfolio.resume.preview', 'compact'))->assertRedirect(route('login'));
});

test('choosing another template rebuilds the pdf right away', function (): void {
    $this->freezeTime();

    $this->actingAs($this->owner)->put(route('portfolio.resume.update'), ['template' => 'compact', 'page_limit' => 2])->assertSessionHasNoErrors();

    expect(Resume::public()->template->value)->toBe('compact');
    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()));
});

test('only this site can frame a template preview', function (): void {
    Vite::useHotFile(storage_path('framework/testing/vite-not-running.hot'));
    Profile::factory()->create();
    $this->actingAs($this->owner);

    expect($this->get(route('portfolio.resume.preview', 'classic'))->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'")
        ->and($this->get(route('portfolio.resume.edit'))->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'");
});
