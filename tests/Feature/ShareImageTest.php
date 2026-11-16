<?php

declare(strict_types=1);

use App\Actions\Portfolio\BuildShareImageAction;
use App\Enums\MediaKey;
use App\Models\Media;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake(BuildShareImageAction::DISK);
    config(['services.chrome.path' => PHP_BINARY]);
    Profile::factory()->create(['name' => 'Ada Lovelace', 'headline' => 'Analyst', 'location' => 'London']);
});

function fakeBrowserWritingScreenshot(): void
{
    Process::fake(function (PendingProcess $process) {
        $output = collect($process->command)->first(fn (string $argument): bool => str_starts_with($argument, '--screenshot='));

        file_put_contents(mb_substr($output, mb_strlen('--screenshot=')), str_repeat('p', 2048));

        return Process::result();
    });
}

function shareImageUrl(string $routeName): string
{
    $url = null;

    test()->get(route($routeName))->assertInertia(function (Assert $page) use (&$url): void {
        $url = $page->toArray()['props']['seo']['image']['url'];
    });

    return $url;
}

test('a public page shares its generated image', function (): void {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('seo.image.url', fn (string $url): bool => str_starts_with($url, route('share-image', ['page' => 'home']) . '?v='))
        ->where('seo.image.width', 1200)
        ->where('seo.image.height', 630)
        ->where('seo.image.type', 'image/png'));
});

test('an uploaded share image replaces the generated ones', function (): void {
    $uploaded = Media::factory()->create(['key' => MediaKey::ShareImage, 'mime_type' => 'image/png']);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('seo.image.url', $uploaded->url()));
});

test('pages outside the public portfolio share no image', function (): void {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('seo.image', null));
});

test('the share image is rendered once and served as a png that never changes', function (): void {
    fakeBrowserWritingScreenshot();
    $url = shareImageUrl('home');

    $this->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    $this->get($url)->assertOk();

    Process::assertRanTimes(fn (PendingProcess $process): bool => in_array('--window-size=1200,630', $process->command, true), 1);
});

test('editing the content gives the share image a new url and replaces the old file', function (): void {
    fakeBrowserWritingScreenshot();
    $oldUrl = shareImageUrl('home');
    $this->get($oldUrl)->assertOk();

    Profile::query()->update(['headline' => 'Mathematician']);
    $newUrl = shareImageUrl('home');

    expect($newUrl)->not->toBe($oldUrl);

    $this->get($oldUrl)->assertOk()->assertHeader('Cache-Control', 'max-age=300, public');

    expect(Storage::disk(BuildShareImageAction::DISK)->files('share-images'))->toHaveCount(1);
});

test('a blank render fails without storing anything', function (): void {
    Process::fake();

    $this->get(route('share-image', ['page' => 'home']))->assertServerError();

    expect(Storage::disk(BuildShareImageAction::DISK)->allFiles())->toBeEmpty();
});

test('the share image is missing until a profile exists', function (): void {
    Profile::query()->delete();

    $this->get(route('share-image', ['page' => 'home']))->assertNotFound();
});

test('unknown pages have no share image', function (): void {
    $this->get('/share/dashboard.png')->assertNotFound();
});
