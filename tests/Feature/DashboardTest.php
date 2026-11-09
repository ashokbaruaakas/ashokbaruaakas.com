<?php

declare(strict_types=1);

use App\Enums\JobApplicationStatus;
use App\Enums\PortfolioList;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\JobHunt;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Models\Conversation;

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
            ->missing('jobHunt')
            ->missing('recentConversations')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('jobHunt', null)
                ->where('recentConversations', [])
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

test('the dashboard summarizes the running job hunt', function () {
    $this->travelTo('2026-11-09 10:00:00');
    JobHunt::factory()->ended()->create(['started_on' => '2026-01-01']);
    $hunt = JobHunt::factory()->create(['title' => 'Senior roles', 'started_on' => '2026-10-01']);
    $application = fn (array $attributes) => JobApplication::factory()->for($hunt, 'hunt')->create($attributes);

    $application(['status' => JobApplicationStatus::Wishlist, 'applied_on' => null, 'company' => 'Acme', 'deadline' => '2026-11-20']);
    $application(['status' => JobApplicationStatus::Applied, 'applied_on' => '2026-10-20']);
    $interview = $application(['status' => JobApplicationStatus::Interviewing, 'company' => 'Globex', 'next_step' => 'Tech interview', 'next_step_on' => '2026-11-11']);
    $application(['status' => JobApplicationStatus::Offer]);
    $application(['status' => JobApplicationStatus::Rejected, 'deadline' => '2026-11-12']);
    $application(['status' => JobApplicationStatus::Applied, 'deadline' => '2026-11-08']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('jobHunt.id', $hunt->id)
                ->where('jobHunt.title', 'Senior roles')
                ->where('jobHunt.stages', ['to_apply' => 1, 'in_progress' => 4, 'closed' => 1])
                ->where('jobHunt.interviewing', 1)
                ->where('jobHunt.offers', 1)
                ->where('jobHunt.needs_follow_up', 1)
                ->where('jobHunt.upcoming', [
                    ['id' => $interview->id, 'company' => 'Globex', 'role' => $interview->role, 'label' => 'Tech interview', 'date' => '2026-11-11'],
                    ['id' => $hunt->applications()->where('company', 'Acme')->value('id'), 'company' => 'Acme', 'role' => $hunt->applications()->where('company', 'Acme')->value('role'), 'label' => 'Deadline', 'date' => '2026-11-20'],
                ])
            )
        );
});

test('the dashboard has no job hunt summary while no hunt is running', function () {
    JobApplication::factory()->for(JobHunt::factory()->ended(), 'hunt')->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('jobHunt', null))
        );
});

test('the dashboard lists the owner\'s three most recent assistant conversations', function () {
    $owner = User::factory()->create();
    $conversation = fn (User $user, string $title, string $updatedAt) => Conversation::query()->forceCreate([
        'id' => (string) Str::uuid7(),
        'participant_type' => $user->getMorphClass(),
        'participant_id' => $user->id,
        'title' => $title,
        'updated_at' => $updatedAt,
    ]);

    $conversation($owner, 'Oldest', '2026-11-01 10:00:00');
    $conversation($owner, 'Newest', '2026-11-05 10:00:00');
    $conversation($owner, 'Middle', '2026-11-03 10:00:00');
    $conversation($owner, 'Second', '2026-11-04 10:00:00');
    $conversation(User::factory()->create(), 'Someone else', '2026-11-06 10:00:00');

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('recentConversations', fn (Collection $conversations): bool => $conversations->pluck('title')->all() === ['Newest', 'Second', 'Middle'])
            )
        );
});

test('the dashboard shows whether the assistant and email are set up', function (?string $apiKey, bool $mailEnabled) {
    config(['ai.providers.anthropic.key' => $apiKey, 'mail.enabled' => $mailEnabled]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ai', ['isReady' => $apiKey !== null, 'provider' => 'Anthropic'])
            ->where('mailEnabled', $mailEnabled)
        );
})->with([
    'ready' => ['sk-ant-test', true],
    'not set up' => [null, false],
]);

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
