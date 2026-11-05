<?php

declare(strict_types=1);

use App\Enums\PortfolioList;
use App\Models\Experience;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the owner can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard defers page availability and content status', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('availability')
            ->missing('contentStatus')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('availability', [
                    'hasHome' => false,
                    'hasResume' => false,
                    'hasOpenSource' => false,
                ])
                ->where('contentStatus.profile', false)
                ->where('contentStatus.images', false)
                ->where('contentStatus.copy', false)
            )
        );
});

test('the dashboard reports public pages as live once they have data', function () {
    Profile::factory()->create(['professional_summary' => null]);
    Experience::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('availability', [
                    'hasHome' => true,
                    'hasResume' => true,
                    'hasOpenSource' => false,
                ])
            )
        );
});

test('the dashboard marks only the portfolio list that has items as filled', function (PortfolioList $list) {
    $list->model()::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('contentStatus', fn (Collection $status): bool => $status->filter()->keys()->all() === [$list->value])
            )
        );
})->with(PortfolioList::cases());

test('the dashboard marks the profile, images and copy sections as filled', function (string $section, Closure $createContent) {
    $createContent();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload->where("contentStatus.{$section}", true))
        );
})->with([
    'profile' => ['profile', fn () => Profile::factory()->create()],
    'images' => ['images', fn () => Media::factory()->create()],
    'section copy' => ['copy', fn () => PortfolioSection::factory()->create()],
    'page copy' => ['copy', fn () => PageContent::factory()->create()],
]);

test('the dashboard shows the account security state', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('twoFactorEnabled', true)
            ->where('passkeyCount', 0)
        );
});

test('pages share the owner name and headline for the head', function () {
    User::factory()->create();
    Profile::factory()->create(['name' => 'Owner Name', 'headline' => 'Owner headline']);

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', 'Owner Name')
            ->where('headline', 'Owner headline')
        );
});

test('pages fall back to the app name and no headline without a profile', function () {
    User::factory()->create();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', config('app.name'))
            ->where('headline', null)
        );
});
