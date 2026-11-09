<?php

declare(strict_types=1);

use App\Enums\MediaKey;
use App\Enums\PortfolioList;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DummySeeder;
use Illuminate\Support\Facades\Storage;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

test('the default seeder seeds nothing', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Profile::query()->count())->toBe(0);
});

test('the dummy seeder fills every part of the portfolio and creates a login', function (): void {
    $this->seed(DummySeeder::class);

    expect(User::query()->sole()->email)->toBe('admin@example.com')
        ->and(Profile::query()->sole()->name)->toBe('Sam Rivera')
        ->and(Media::query()->count())->toBe(count(MediaKey::cases()) - 1)
        ->and(PortfolioSection::query()->count())->toBeGreaterThan(0)
        ->and(PageContent::query()->count())->toBeGreaterThan(0);

    foreach (PortfolioList::cases() as $list) {
        expect($list->model()::query()->count())->toBeGreaterThan(0, "{$list->value} has no rows");
    }
});

test('the dummy seeder makes every public page available without personal details', function (string $routeName): void {
    $this->seed(DummySeeder::class);

    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('Sam Rivera')
        ->assertDontSee('ashokbaruaakas@gmail.com')
        ->assertDontSee('SiPay')
        ->assertDontSee('Grow More Gaze')
        ->assertDontSee('Chattogram');
})->with(['home', 'resume', 'open-source']);

test('the dummy seeder keeps an existing owner account', function (): void {
    $owner = User::factory()->create();

    $this->seed(DummySeeder::class);

    expect(User::query()->sole()->is($owner))->toBeTrue()
        ->and(Profile::query()->count())->toBe(1);
});

test('the dummy seeder replaces the profile owner:create starts with', function (): void {
    $owner = User::factory()->create();
    Profile::factory()->nameOnly()->create(['name' => $owner->name, 'email' => $owner->email]);

    $this->seed(DummySeeder::class);

    expect(Profile::query()->sole()->name)->toBe('Sam Rivera')
        ->and(User::query()->sole()->is($owner))->toBeTrue();
});

test('the dummy seeder leaves existing content alone', function (): void {
    $profile = Profile::factory()->create(['name' => 'Real Owner']);

    $this->seed(DummySeeder::class);

    expect(Profile::query()->sole()->is($profile))->toBeTrue()
        ->and(User::query()->count())->toBe(0)
        ->and(Media::query()->count())->toBe(0);
});

test('the dummy seeder does nothing outside the local environment', function (string $environment): void {
    app()->detectEnvironment(fn (): string => $environment);

    $this->seed(DummySeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Profile::query()->count())->toBe(0);
})->with(['production', 'staging']);
