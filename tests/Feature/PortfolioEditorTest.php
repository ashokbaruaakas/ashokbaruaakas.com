<?php

declare(strict_types=1);

use App\Enums\MediaKey;
use App\Enums\PortfolioList;
use App\Enums\PortfolioMediaSlot;
use App\Models\Experience;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\AshokBaruaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = User::factory()->create();
});

function editorPages(): array
{
    return [
        'profile' => ['GET', '/portfolio/profile'],
        'save profile' => ['PUT', '/portfolio/profile'],
        'copy' => ['GET', '/portfolio/copy'],
        'save copy' => ['PUT', '/portfolio/copy'],
        'media' => ['GET', '/portfolio/media'],
        'upload' => ['POST', '/portfolio/media/avatar'],
        'remove media' => ['DELETE', '/portfolio/media/avatar'],
        'list' => ['GET', '/portfolio/skills'],
        'add item' => ['POST', '/portfolio/skills'],
        'order' => ['PUT', '/portfolio/skills/order'],
        'edit item' => ['PUT', '/portfolio/skills/1'],
        'remove item' => ['DELETE', '/portfolio/skills/1'],
    ];
}

test('guests are sent to log in', function (string $method, string $url) {
    $this->call($method, $url)->assertRedirect(route('login'));
})->with(editorPages());

test('every list page shows its fields and items', function (PortfolioList $list) {
    $this->seed(AshokBaruaSeeder::class);

    $this->actingAs($this->owner)
        ->get(route('portfolio.lists.index', $list->value))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portfolio/List')
            ->where('list.key', $list->value)
            ->has('fields', count($list->fields()))
            ->has('items', $list->model()::query()->count()));
})->with(PortfolioList::cases());

test('the first profile save publishes the home page on an empty install', function () {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->component('Welcome'));

    $this->actingAs($this->owner)
        ->put(route('portfolio.profile.update'), ['name' => 'New Owner', 'alternate_names' => ['N. Owner']])
        ->assertSessionHasNoErrors();

    expect(Profile::query()->sole()->alternate_names)->toBe(['N. Owner']);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->component('Home')
        ->where('portfolio.name', 'New Owner'));
});

test('saving the profile updates the existing one', function () {
    Profile::factory()->create();

    $this->actingAs($this->owner)
        ->put(route('portfolio.profile.update'), ['name' => 'Renamed'])
        ->assertSessionHasNoErrors();

    expect(Profile::query()->sole()->name)->toBe('Renamed');
});

test('the profile needs a name and safe links', function (array $input, string $errorKey) {
    $this->actingAs($this->owner)
        ->put(route('portfolio.profile.update'), $input)
        ->assertSessionHasErrors($errorKey);
})->with([
    'no name' => [['name' => ''], 'name'],
    'javascript website' => [['name' => 'Owner', 'website_url' => 'javascript:alert(1)'], 'website_url'],
    'invalid email' => [['name' => 'Owner', 'email' => 'nope'], 'email'],
]);

test('an added item goes to the end of its list and reaches public pages', function () {
    Profile::factory()->create();
    Testimonial::factory()->create(['sort_order' => 5]);

    $this->actingAs($this->owner)
        ->post(route('portfolio.lists.store', 'testimonials'), [
            'quote' => 'Great to work with.',
            'author' => 'New Author',
            'role' => 'CTO',
            'company' => 'Acme',
        ])
        ->assertSessionHasNoErrors();

    expect(Testimonial::query()->where('author', 'New Author')->sole()->sort_order)->toBe(6);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('portfolio.testimonials.1.author', 'New Author'));
});

test('list items are validated against their fields', function (string $list, array $input, string $errorKey) {
    $this->actingAs($this->owner)
        ->post(route('portfolio.lists.store', $list), $input)
        ->assertSessionHasErrors($errorKey);
})->with([
    'missing required' => ['testimonials', ['author' => 'A', 'role' => 'B', 'company' => 'C'], 'quote'],
    'javascript link' => ['social-links', ['platform' => 'X', 'url' => 'javascript:alert(1)', 'icon' => 'send'], 'url'],
    'unknown icon' => ['social-links', ['platform' => 'X', 'url' => 'https://x.com', 'icon' => 'nope'], 'icon'],
    'javascript project link' => ['projects', ['links' => [['label' => 'A', 'url' => 'javascript:alert(1)']]], 'links.0.url'],
]);

test('an item only accepts the fields in its schema', function () {
    Testimonial::factory()->create();
    $item = Testimonial::query()->sole();

    $this->actingAs($this->owner)
        ->put(route('portfolio.lists.update', ['list' => 'testimonials', 'item' => $item->id]), [
            'quote' => 'Updated.',
            'author' => 'A',
            'role' => 'B',
            'company' => 'C',
            'sort_order' => 99,
        ])
        ->assertSessionHasNoErrors();

    expect($item->refresh()->quote)->toBe('Updated.')
        ->and($item->sort_order)->not->toBe(99);
});

test('project links are replaced in the submitted order', function () {
    $project = Project::factory()->create();
    $project->links()->create(['label' => 'Old', 'url' => 'https://old.test', 'sort_order' => 0]);

    $this->actingAs($this->owner)
        ->put(route('portfolio.lists.update', ['list' => 'projects', 'item' => $project->id]), [
            'name' => $project->name,
            'description' => 'Description',
            'language' => 'PHP',
            'tier' => '1',
            'technologies' => ['Laravel'],
            'stars' => 0,
            'links' => [
                ['label' => 'First', 'url' => 'https://first.test', 'is_primary' => true],
                ['label' => 'Second', 'url' => 'https://second.test', 'is_primary' => false],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($project->links()->pluck('label')->all())->toBe(['First', 'Second'])
        ->and($project->refresh()->tier)->toBe(1);
});

test('experience can be saved without a description', function () {
    Profile::factory()->create();

    $this->actingAs($this->owner)
        ->post(route('portfolio.lists.store', 'experience'), [
            'role' => 'Engineer',
            'company' => 'Acme',
            'period' => '2024',
            'highlights' => [],
        ])
        ->assertSessionHasNoErrors();

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('portfolio.experience.0.description', ''));
    expect(Experience::query()->sole()->description)->toBeNull();
});

test('items can be reordered and removed', function () {
    $first = Testimonial::factory()->create(['sort_order' => 0]);
    $second = Testimonial::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->owner)
        ->put(route('portfolio.lists.order.update', 'testimonials'), ['ids' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    expect($second->refresh()->sort_order)->toBe(0)->and($first->refresh()->sort_order)->toBe(1);

    $this->actingAs($this->owner)
        ->delete(route('portfolio.lists.destroy', ['list' => 'testimonials', 'item' => $first->id]))
        ->assertRedirect();

    expect(Testimonial::query()->pluck('id')->all())->toBe([$second->id]);
});

test('removing an item that does not exist returns not found', function () {
    $this->actingAs($this->owner)
        ->delete(route('portfolio.lists.destroy', ['list' => 'testimonials', 'item' => 999]))
        ->assertNotFound();
});

test('an unknown list or slot returns not found', function (string $url) {
    $this->actingAs($this->owner)->get($url)->assertNotFound();
})->with([
    'list' => '/portfolio/users',
]);

test('section and page copy is saved and reaches public pages', function () {
    Profile::factory()->create();

    $this->actingAs($this->owner)
        ->put(route('portfolio.copy.update'), [
            'sections' => ['work' => ['eyebrow' => 'Work', 'title' => 'Selected Work', 'description' => null, 'labels' => ['flagship' => 'Main']]],
            'pages' => ['home' => ['title' => 'Custom Title', 'description' => 'About me', 'image_alt' => null]],
        ])
        ->assertSessionHasNoErrors();

    expect(PortfolioSection::query()->where('key', 'work')->sole()->labels)->toBe(['flagship' => 'Main'])
        ->and(PageContent::query()->where('key', 'home')->sole()->title)->toBe('Custom Title');

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('portfolio.sections.work.title', 'Selected Work')
        ->where('portfolio.pages.home.title', 'Custom Title'));
});

test('copy only accepts known sections and pages', function () {
    $this->actingAs($this->owner)
        ->put(route('portfolio.copy.update'), [
            'sections' => ['unknown' => ['title' => 'Nope']],
            'pages' => ['home' => ['title' => 'Home']],
        ])
        ->assertSessionHasErrors('sections');
});

test('the portrait upload produces both sizes as webp under hashed names', function () {
    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', 'portrait'), ['file' => UploadedFile::fake()->image('me.jpg', 1200, 1000)])
        ->assertSessionHasNoErrors();

    foreach ([[MediaKey::Portrait640, 640], [MediaKey::Portrait960, 960]] as [$key, $size]) {
        $media = Media::query()->where('key', $key)->sole();
        $image = getimagesizefromstring(Storage::disk('public')->get($media->path));

        expect($image[0])->toBe($size)
            ->and($image[1])->toBe($size)
            ->and($image['mime'])->toBe('image/webp')
            ->and($media->path)->toMatch("/^owner\/{$key->value}-[a-f0-9]{16}\.webp$/");
    }
})->skip(fn () => ! function_exists('imagewebp'), 'GD has no WebP support.');

test('replacing a file removes the old one and changes its URL', function () {
    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', 'avatar'), ['file' => UploadedFile::fake()->image('a.png', 200, 200)->size(10)]);

    $firstPath = Media::query()->where('key', MediaKey::Avatar)->sole()->path;

    $image = imagecreatetruecolor(300, 300);
    imagefilledrectangle($image, 0, 0, 299, 299, imagecolorallocate($image, 200, 10, 10));
    $file = tempnam(sys_get_temp_dir(), 'avatar');
    imagepng($image, $file);

    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', 'avatar'), ['file' => new UploadedFile($file, 'b.png', 'image/png', null, true)])
        ->assertSessionHasNoErrors();

    $secondPath = Media::query()->where('key', MediaKey::Avatar)->sole()->path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('uploads that could run code or do not fit are rejected', function (string $slot, callable $file) {
    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', $slot), ['file' => $file()])
        ->assertSessionHasErrors('file');

    expect(Media::query()->count())->toBe(0);
})->with([
    'svg' => ['avatar', fn () => UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
    'php as image' => ['avatar', fn () => UploadedFile::fake()->createWithContent('a.png', '<?php phpinfo();')],
    'php file' => ['favicon-ico', fn () => UploadedFile::fake()->createWithContent('a.php', '<?php phpinfo();')],
    'too small' => ['portrait', fn () => UploadedFile::fake()->image('a.jpg', 200, 200)],
    'wrong share size' => ['share-image', fn () => UploadedFile::fake()->image('a.png', 800, 600)],
    'share not png' => ['share-image', fn () => UploadedFile::fake()->image('a.jpg', 1200, 630)],
]);

test('an image of the wrong size is rejected with the size it needs', function (string $slot, int $width, int $height, string $message) {
    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', $slot), ['file' => UploadedFile::fake()->image('a.png', $width, $height)])
        ->assertSessionHasErrors(['file' => $message]);
})->with([
    'too small' => ['portrait', 200, 200, 'This image must be at least 960 × 960 pixels.'],
    'not the exact size' => ['share-image', 800, 600, 'This image must be exactly 1200 × 630 pixels.'],
]);

test('the seeded favicon can be uploaded unchanged', function () {
    $source = database_path('seeders/owners/ashok-barua/favicon.ico');

    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', 'favicon-ico'), ['file' => new UploadedFile($source, 'favicon.ico', 'image/x-icon', null, true)])
        ->assertSessionHasNoErrors();

    expect(Storage::disk('public')->get(Media::query()->where('key', MediaKey::FaviconIco)->sole()->path))->toBe(file_get_contents($source));
});

test('removing a slot deletes all its files', function () {
    $this->actingAs($this->owner)
        ->post(route('portfolio.media.update', PortfolioMediaSlot::Portrait->value), ['file' => UploadedFile::fake()->image('me.jpg', 1000, 1000)]);

    $paths = Media::query()->pluck('path')->all();

    $this->actingAs($this->owner)
        ->delete(route('portfolio.media.destroy', 'portrait'))
        ->assertRedirect();

    expect(Media::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($paths[0]);
});

test('the media page lists every slot with its current file', function () {
    $this->seed(AshokBaruaSeeder::class);

    $this->actingAs($this->owner)
        ->get(route('portfolio.media.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('slots', count(PortfolioMediaSlot::cases()))
            ->where('slots.0.key', 'portrait')
            ->where('slots.0.url', Media::query()->where('key', MediaKey::Portrait960)->sole()->url()));
});
