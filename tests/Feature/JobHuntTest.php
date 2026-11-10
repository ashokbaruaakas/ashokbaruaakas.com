<?php

declare(strict_types=1);

use App\Enums\JobApplicationEventType;
use App\Enums\JobApplicationStatus;
use App\Enums\JobHuntOutcome;
use App\Enums\JobRelocation;
use App\Enums\WorkMode;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobHunt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->travelTo('2026-11-08 10:00:00');
});

function signIn(): void
{
    test()->actingAs(User::factory()->create());
}

test('guests are sent to log in', function (string $method, string $url): void {
    $this->call($method, $url)->assertRedirect(route('login'));
})->with([
    'index' => ['GET', '/job-hunt'],
    'start' => ['POST', '/job-hunt'],
    'show' => ['GET', '/job-hunt/1'],
    'update' => ['PUT', '/job-hunt/1'],
    'delete' => ['DELETE', '/job-hunt/1'],
    'end' => ['POST', '/job-hunt/1/end'],
    'add job' => ['POST', '/job-hunt/1/applications'],
    'edit job' => ['PUT', '/job-hunt/1/applications/1'],
    'remove job' => ['DELETE', '/job-hunt/1/applications/1'],
    'change status' => ['PUT', '/job-hunt/1/applications/1/status'],
    'add note' => ['POST', '/job-hunt/1/applications/1/notes'],
    'remove note' => ['DELETE', '/job-hunt/1/applications/1/notes/1'],
]);

test('the index lists the running hunt first with its counts', function (): void {
    signIn();
    $past = JobHunt::factory()->ended()->create(['title' => 'Past', 'started_on' => '2026-10-01']);
    $running = JobHunt::factory()->create(['title' => 'Running', 'started_on' => '2026-09-01']);
    JobApplication::factory()->for($running, 'hunt')->wishlist()->create();
    JobApplication::factory()->for($running, 'hunt')->create(['status' => JobApplicationStatus::Applied]);
    JobApplication::factory()->for($running, 'hunt')->create(['status' => JobApplicationStatus::Offer]);
    JobApplication::factory()->for($running, 'hunt')->create(['status' => JobApplicationStatus::Ignored, 'applied_on' => null]);

    $this->get(route('job-hunt.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('job-hunt/Index')
            ->where('hunts.0.id', $running->id)
            ->where('hunts.0.applications_count', 4)
            ->where('hunts.0.applied_count', 2)
            ->where('hunts.0.offers_count', 1)
            ->where('hunts.1.id', $past->id));
});

test('starting a hunt saves it and opens it', function (): void {
    signIn();

    $response = $this->post(route('job-hunt.store'), ['title' => 'Laravel roles', 'goal' => 'Remote, senior', 'started_on' => '2026-11-01']);

    $hunt = JobHunt::query()->sole();

    $response->assertRedirect(route('job-hunt.show', $hunt));
    expect($hunt->title)->toBe('Laravel roles')
        ->and($hunt->goal)->toBe('Remote, senior')
        ->and($hunt->started_on->toDateString())->toBe('2026-11-01')
        ->and($hunt->isActive())->toBeTrue();
});

test('a new hunt is refused while another is running', function (): void {
    signIn();
    JobHunt::factory()->create();

    $this->post(route('job-hunt.store'), ['title' => 'Second', 'started_on' => '2026-11-08'])
        ->assertSessionHasErrors(['title' => 'End your current hunt before starting a new one.']);

    expect(JobHunt::query()->count())->toBe(1);
});

test('a hunt needs a name and a start date that is not in the future', function (): void {
    signIn();

    $this->post(route('job-hunt.store'), [])->assertSessionHasErrors(['title', 'started_on']);
    $this->post(route('job-hunt.store'), ['title' => 'Later', 'started_on' => '2026-11-09'])->assertSessionHasErrors('started_on');

    expect(JobHunt::query()->exists())->toBeFalse();
});

test('editing a hunt saves its details', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();

    $this->put(route('job-hunt.update', $hunt), ['title' => 'Renamed', 'notes' => 'Keep going', 'started_on' => '2026-10-15'])
        ->assertSessionHasNoErrors();

    expect($hunt->fresh())
        ->title->toBe('Renamed')
        ->notes->toBe('Keep going');
});

test('a hunt saves the work modes, countries and relocation the owner wants, and unticked boxes clear them', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();

    $this->put(route('job-hunt.update', $hunt), [
        'title' => 'EU roles',
        'started_on' => '2026-10-15',
        'work_modes' => ['remote', 'hybrid'],
        'countries' => 'Germany, Netherlands',
        'open_to_relocation' => '1',
    ])->assertSessionHasNoErrors();

    expect($hunt->fresh())
        ->work_modes->all()->toBe([WorkMode::Remote, WorkMode::Hybrid])
        ->countries->toBe('Germany, Netherlands')
        ->open_to_relocation->toBeTrue();

    $this->put(route('job-hunt.update', $hunt), ['title' => 'EU roles', 'started_on' => '2026-10-15'])->assertSessionHasNoErrors();

    expect($hunt->fresh())
        ->work_modes->all()->toBe([])
        ->open_to_relocation->toBeFalse();

    $this->put(route('job-hunt.update', $hunt), ['title' => 'EU roles', 'started_on' => '2026-10-15', 'work_modes' => ['anywhere']])
        ->assertSessionHasErrors('work_modes.0');
});

test('a job saves its work mode and relocation, and leaves relocation empty when the post does not say', function (): void {
    signIn();
    $application = JobApplication::factory()->create();

    $this->put(route('job-hunt.applications.update', [$application->hunt, $application]), [
        'company' => 'Acme', 'role' => 'Lead', 'country' => 'Germany', 'location' => 'Berlin', 'work_mode' => 'on_site', 'relocation' => 'offered',
    ])->assertSessionHasNoErrors();

    expect($application->fresh())
        ->work_mode->toBe(WorkMode::OnSite)
        ->relocation->toBe(JobRelocation::Offered);

    $this->put(route('job-hunt.applications.update', [$application->hunt, $application]), ['company' => 'Acme', 'role' => 'Lead', 'work_mode' => 'office'])
        ->assertSessionHasErrors('work_mode');

    $this->put(route('job-hunt.applications.update', [$application->hunt, $application]), ['company' => 'Acme', 'role' => 'Lead', 'work_mode' => 'remote', 'relocation' => null])
        ->assertSessionHasNoErrors();

    expect($application->fresh())
        ->work_mode->toBe(WorkMode::Remote)
        ->relocation->toBeNull();
});

test('jobs saved before work modes existed get theirs from the country or city', function (): void {
    $migration = require database_path('migrations/2026_11_09_130519_add_work_mode_and_relocation_to_job_applications_table.php');
    $migration->down();

    $hunt = JobHunt::factory()->create();
    $insert = fn (?string $country, ?string $location): int => DB::table('job_applications')->insertGetId([
        'job_hunt_id' => $hunt->id, 'company' => 'Acme', 'role' => 'Engineer', 'status' => 'applied', 'country' => $country, 'location' => $location,
    ]);
    $remote = $insert('Remote', null);
    $hybrid = $insert('Germany', 'Berlin, hybrid');
    $onSite = $insert('Spain', 'On-site · Madrid');
    $unknown = $insert('France', 'Paris');
    $described = $insert('Netherlands', 'Amsterdam, hybrid 2-3 office days (needs visa sponsorship)');
    $office = $insert('Bangladesh', 'Agrabad, Chattogram (office)');

    $migration->up();

    $read = fn (int $id): array => (array) DB::table('job_applications')->where('id', $id)->first(['country', 'location', 'work_mode']);

    expect($read($remote))->toBe(['country' => null, 'location' => null, 'work_mode' => 'remote'])
        ->and($read($hybrid))->toBe(['country' => 'Germany', 'location' => 'Berlin', 'work_mode' => 'hybrid'])
        ->and($read($onSite))->toBe(['country' => 'Spain', 'location' => 'Madrid', 'work_mode' => 'on_site'])
        ->and($read($unknown))->toBe(['country' => 'France', 'location' => 'Paris', 'work_mode' => null])
        ->and($read($described))->toBe(['country' => 'Netherlands', 'location' => 'Amsterdam, hybrid 2-3 office days (needs visa sponsorship)', 'work_mode' => 'hybrid'])
        ->and($read($office))->toBe(['country' => 'Bangladesh', 'location' => 'Agrabad, Chattogram (office)', 'work_mode' => 'on_site']);
});

test('the hunt page flags jobs waiting too long for a reply', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();
    $stale = JobApplication::factory()->for($hunt, 'hunt')->create(['applied_on' => '2026-10-29']);
    $recent = JobApplication::factory()->for($hunt, 'hunt')->create(['applied_on' => '2026-10-30']);
    $followedUp = JobApplication::factory()->for($hunt, 'hunt')->create(['applied_on' => '2026-10-01']);
    JobApplicationEvent::factory()->for($followedUp, 'application')->create(['occurred_on' => '2026-11-05']);
    $interviewing = JobApplication::factory()->for($hunt, 'hunt')->create(['status' => JobApplicationStatus::Interviewing, 'applied_on' => '2026-10-01']);

    $flags = [];

    $this->get(route('job-hunt.show', $hunt))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$flags) {
            $page->component('job-hunt/Show')->where('hunt.is_active', true)->has('applications', 4);

            $flags = collect($page->toArray()['props']['applications'])->pluck('needs_follow_up', 'id')->all();
        });

    expect($flags)->toBe([
        $stale->id => true,
        $recent->id => false,
        $followedUp->id => false,
        $interviewing->id => false,
    ]);
});

test('adding an applied job fills in the applied date and starts its timeline', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();

    $this->post(route('job-hunt.applications.store', $hunt), ['status' => 'applied', 'company' => 'Acme', 'role' => 'Senior Engineer', 'chance' => 'high'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $application = $hunt->applications()->sole();

    expect($application->applied_on?->toDateString())->toBe('2026-11-08')
        ->and($application->events()->sole()->only(['type', 'from_status', 'to_status']))->toBe([
            'type' => JobApplicationEventType::StatusChange,
            'from_status' => null,
            'to_status' => JobApplicationStatus::Applied,
        ]);
});

test('a wishlist job has no applied date', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();

    $this->post(route('job-hunt.applications.store', $hunt), ['status' => 'wishlist', 'company' => 'Acme', 'role' => 'Engineer'])
        ->assertSessionHasNoErrors();

    expect($hunt->applications()->sole()->applied_on)->toBeNull();
});

test('a job needs a status, company and role, and a link must be a web address', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();

    $this->post(route('job-hunt.applications.store', $hunt), [])->assertSessionHasErrors(['status', 'company', 'role']);
    $this->post(route('job-hunt.applications.store', $hunt), ['status' => 'applied', 'company' => 'Acme', 'role' => 'Engineer', 'link' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('link');

    expect($hunt->applications()->exists())->toBeFalse();
});

test('editing a job saves its details but not its status', function (): void {
    signIn();
    $application = JobApplication::factory()->create(['status' => JobApplicationStatus::Applied]);
    $hunt = $application->hunt;

    $this->put(route('job-hunt.applications.update', [$hunt, $application]), ['company' => 'Acme', 'role' => 'Lead', 'status' => 'offer'])
        ->assertSessionHasErrors('status');

    $this->put(route('job-hunt.applications.update', [$hunt, $application]), ['company' => 'Acme', 'role' => 'Lead', 'next_step' => 'Final round', 'next_step_on' => '2026-11-12'])
        ->assertSessionHasNoErrors();

    expect($application->fresh())
        ->role->toBe('Lead')
        ->next_step->toBe('Final round')
        ->status->toBe(JobApplicationStatus::Applied);
});

test('changing the status records it on the timeline and dates the first application', function (): void {
    signIn();
    $application = JobApplication::factory()->wishlist()->create();

    $this->put(route('job-hunt.applications.status.update', [$application->hunt, $application]), ['status' => 'applied'])
        ->assertSessionHasNoErrors();

    $application->refresh();

    expect($application->status)->toBe(JobApplicationStatus::Applied)
        ->and($application->applied_on?->toDateString())->toBe('2026-11-08')
        ->and($application->events()->sole()->only(['from_status', 'to_status']))->toBe([
            'from_status' => JobApplicationStatus::Wishlist,
            'to_status' => JobApplicationStatus::Applied,
        ]);
});

test('setting the same status again adds nothing to the timeline', function (): void {
    signIn();
    $application = JobApplication::factory()->create(['status' => JobApplicationStatus::Applied]);

    $this->put(route('job-hunt.applications.status.update', [$application->hunt, $application]), ['status' => 'applied']);

    expect($application->events()->exists())->toBeFalse();
});

test('notes can be added to and removed from the timeline', function (): void {
    signIn();
    $application = JobApplication::factory()->create();

    $this->post(route('job-hunt.applications.notes.store', [$application->hunt, $application]), ['body' => '  HR call booked ', 'occurred_on' => '2026-11-07'])
        ->assertSessionHasNoErrors();

    $note = $application->events()->sole();

    expect($note->body)->toBe('HR call booked')
        ->and($note->occurred_on->toDateString())->toBe('2026-11-07');

    $this->delete(route('job-hunt.applications.notes.destroy', [$application->hunt, $application, $note]))->assertRedirect();

    expect($application->events()->exists())->toBeFalse();
});

test('status changes cannot be removed from the timeline', function (): void {
    signIn();
    $application = JobApplication::factory()->create();
    $event = JobApplicationEvent::factory()->for($application, 'application')->create(['type' => JobApplicationEventType::StatusChange, 'body' => null, 'to_status' => JobApplicationStatus::Applied]);

    $this->delete(route('job-hunt.applications.notes.destroy', [$application->hunt, $application, $event]))->assertNotFound();

    expect($event->fresh())->not->toBeNull();
});

test('a job cannot be reached through another hunt', function (): void {
    signIn();
    $application = JobApplication::factory()->create();
    $otherHunt = JobHunt::factory()->ended()->create();

    $this->delete(route('job-hunt.applications.destroy', [$otherHunt, $application]))->assertNotFound();

    expect($application->fresh())->not->toBeNull();
});

test('removing a job removes its timeline', function (): void {
    signIn();
    $application = JobApplication::factory()->create();
    JobApplicationEvent::factory()->for($application, 'application')->create();

    $this->delete(route('job-hunt.applications.destroy', [$application->hunt, $application]))->assertRedirect();

    expect(JobApplication::query()->exists())->toBeFalse()
        ->and(JobApplicationEvent::query()->exists())->toBeFalse();
});

test('ending a hunt can close the jobs still waiting on a reply', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create(['started_on' => '2026-09-01']);
    $waiting = JobApplication::factory()->for($hunt, 'hunt')->create(['status' => JobApplicationStatus::Applied]);
    $interviewing = JobApplication::factory()->for($hunt, 'hunt')->create(['status' => JobApplicationStatus::Interviewing]);

    $this->post(route('job-hunt.end', $hunt), ['outcome' => 'hired', 'ended_on' => '2026-11-06', 'close_waiting_applications' => '1'])
        ->assertSessionHasNoErrors();

    $hunt->refresh();

    expect($hunt->ended_on?->toDateString())->toBe('2026-11-06')
        ->and($hunt->outcome)->toBe(JobHuntOutcome::Hired)
        ->and($waiting->fresh()->status)->toBe(JobApplicationStatus::NoResponse)
        ->and($waiting->events()->sole()->occurred_on->toDateString())->toBe('2026-11-06')
        ->and($interviewing->fresh()->status)->toBe(JobApplicationStatus::Interviewing);
});

test('ending a hunt leaves waiting jobs alone unless asked', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create();
    $waiting = JobApplication::factory()->for($hunt, 'hunt')->create(['status' => JobApplicationStatus::Applied]);

    $this->post(route('job-hunt.end', $hunt), ['outcome' => 'paused', 'ended_on' => '2026-11-08'])->assertSessionHasNoErrors();

    expect($hunt->fresh()->isActive())->toBeFalse()
        ->and($waiting->fresh()->status)->toBe(JobApplicationStatus::Applied);
});

test('a hunt cannot end before it started', function (): void {
    signIn();
    $hunt = JobHunt::factory()->create(['started_on' => '2026-11-01']);

    $this->post(route('job-hunt.end', $hunt), ['outcome' => 'hired', 'ended_on' => '2026-10-31'])->assertSessionHasErrors('ended_on');

    expect($hunt->fresh()->isActive())->toBeTrue();
});

test('a hunt that has ended cannot end again', function (): void {
    signIn();
    $hunt = JobHunt::factory()->ended(JobHuntOutcome::Paused)->create();

    $this->post(route('job-hunt.end', $hunt), ['outcome' => 'hired', 'ended_on' => '2026-11-08'])->assertConflict();

    expect($hunt->fresh()->outcome)->toBe(JobHuntOutcome::Paused);
});

test('deleting a hunt removes its jobs', function (): void {
    signIn();
    $application = JobApplication::factory()->create();

    $this->delete(route('job-hunt.destroy', $application->hunt))->assertRedirect(route('job-hunt.index'));

    expect(JobHunt::query()->exists())->toBeFalse()
        ->and(JobApplication::query()->exists())->toBeFalse();
});
