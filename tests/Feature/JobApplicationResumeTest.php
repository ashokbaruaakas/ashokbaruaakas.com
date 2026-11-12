<?php

declare(strict_types=1);

use App\Enums\ResumeTemplate;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Bus::fake();
    $this->owner = User::factory()->create();
    $this->job = JobApplication::factory()->create(['company' => 'Initech', 'role' => 'Payments Engineer']);
    $this->storeUrl = route('job-hunt.applications.resume.store', ['jobHunt' => $this->job->job_hunt_id, 'application' => $this->job->id]);
});

test('a job gets a resume tailored for it, copied from the public one and opened with the assistant', function (): void {
    $experience = Experience::factory()->create();
    Resume::public()->update([
        'template' => ResumeTemplate::Sidebar,
        'selection' => ['experience' => [['id' => $experience->id, 'included' => false]]],
    ]);

    $this->actingAs($this->owner)->post($this->storeUrl)
        ->assertRedirect(route('resumes.edit', ['resume' => $tailored = Resume::query()->latest('id')->first(), 'assistant' => 1]));

    expect($tailored)
        ->name->toBe('Initech — Payments Engineer')
        ->job_application_id->toBe($this->job->id)
        ->is_public->toBeFalse()
        ->template->toBe(ResumeTemplate::Sidebar)
        ->and($tailored->selectionFor(App\Enums\ResumeList::Experience)[0]['included'])->toBeFalse();

    $this->get(route('resumes.edit', ['resume' => $tailored, 'assistant' => 1]))->assertInertia(fn (Assert $page) => $page->where('opensAssistant', true));
});

test('a job that already has a tailored resume opens it instead of copying again', function (): void {
    $this->actingAs($this->owner)->post($this->storeUrl);
    $this->post($this->storeUrl)->assertRedirect();

    expect(Resume::query()->where('job_application_id', $this->job->id)->count())->toBe(1);
});

test('the hunt shows which resume is tailored for each job and names the public one', function (): void {
    $tailored = Resume::factory()->create(['name' => 'For Initech', 'job_application_id' => $this->job->id]);

    $this->actingAs($this->owner)->get(route('job-hunt.show', $this->job->job_hunt_id))->assertInertia(fn (Assert $page) => $page
        ->where('tailoredResumes', [['id' => $tailored->id, 'name' => 'For Initech', 'jobApplicationId' => $this->job->id]])
        ->where('publicResumeName', Resume::public()->name));
});

test('guests cannot tailor a resume', function (): void {
    $this->post($this->storeUrl)->assertRedirect(route('login'));

    expect(Resume::query()->count())->toBe(1);
});
