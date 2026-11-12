<?php

declare(strict_types=1);

use App\Enums\JobApplicationStatus;
use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use App\Jobs\RebuildResumePdf;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\ResumeBuild;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Bus::fake();
    Storage::fake(ResumeBuild::DISK);
    $this->owner = User::factory()->create();
    Profile::factory()->create(['tagline' => 'Engineer — Backend', 'professional_summary' => 'Builds things.']);
});

/**
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function resumeForm(Resume $resume, array $changes = []): array
{
    return ['name' => $resume->name, 'template' => $resume->template->value, 'accent' => $resume->accent?->value, 'font' => $resume->font?->value, ...$changes];
}

test('the old settings address opens the public resume', function (): void {
    $this->actingAs($this->owner)->get(route('resumes.public.edit'))->assertRedirect(route('resumes.edit', Resume::public()));
});

test('the owner sees every resume', function (): void {
    Resume::factory()->create(['name' => 'Backend']);

    $this->actingAs($this->owner)->get(route('resumes.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('resumes/Index')
        ->where('resumes.0.name', 'Main')
        ->where('resumes.0.isPublic', true)
        ->where('resumes.1.name', 'Backend'));
});

test('a new resume includes every portfolio item and builds right away', function (): void {
    $experience = Experience::factory()->create();
    $this->freezeTime();

    $this->actingAs($this->owner)->post(route('resumes.store'), ['name' => 'Backend Roles'])->assertRedirect();

    $resume = Resume::query()->where('slug', 'backend-roles')->sole();
    expect($resume->is_public)->toBeFalse()
        ->and($resume->selectionFor(ResumeList::Experience))->toBe([['id' => $experience->id, 'included' => true]]);
    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()));
});

test('a duplicate copies the settings, selection and overrides under a unique address', function (): void {
    $source = Resume::factory()->create(['name' => 'Backend', 'slug' => 'backend', 'template' => ResumeTemplate::Compact, 'accent' => ResumeAccent::Blue, 'font' => ResumeFont::Poppins, 'overrides' => ['summary' => ['text' => 'Short.', 'base_hash' => 'x']]]);

    $this->actingAs($this->owner)->post(route('resumes.store'), ['name' => 'Backend', 'duplicate_of' => $source->id]);

    expect(Resume::query()->where('slug', 'backend-2')->sole())
        ->template->toBe(ResumeTemplate::Compact)
        ->accent->toBe(ResumeAccent::Blue)
        ->font->toBe(ResumeFont::Poppins)
        ->textOverride('summary')->toBe('Short.');
});

test('the editor lists items in the resume order with the overrides beside the text they replace', function (): void {
    $first = Experience::factory()->create(['role' => 'First', 'company' => 'Acme', 'sort_order' => 0, 'highlights' => ['Shipped A']]);
    $second = Experience::factory()->create(['role' => 'Second', 'company' => 'Globex', 'sort_order' => 1]);
    $resume = Resume::factory()->create([
        'selection' => ['experience' => [['id' => $second->id, 'included' => true]]],
        'overrides' => ['highlights' => [$first->id => ['items' => ['Cut costs'], 'base_hash' => Resume::baseHash(['Shipped before'])]]],
    ]);

    $this->actingAs($this->owner)->get(route('resumes.edit', $resume))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('resumes/Edit')
        ->where('selection.experience.0', ['id' => $second->id, 'label' => 'Second — Globex', 'included' => true])
        ->where('selection.experience.1', ['id' => $first->id, 'label' => 'First — Acme', 'included' => false])
        ->where('overrides.highlights.0.override', ['Cut costs'])
        ->where('overrides.highlights.0.isStale', true)
        ->where('overrides.headline.base', 'Engineer — Backend'));
});

test('saving stores the selection and overrides and schedules a rebuild', function (): void {
    $experience = Experience::factory()->create(['highlights' => ['Shipped A']]);
    $resume = Resume::factory()->create();
    $this->freezeTime();

    $this->actingAs($this->owner)->put(route('resumes.update', $resume), resumeForm($resume, [
        'selection' => ['experience' => [['id' => $experience->id, 'included' => true]]],
        'overrides' => ['headline' => 'Staff Engineer', 'summary' => '', 'highlights' => [$experience->id => ['Cut costs by 20%', ' ']]],
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $resume->refresh();
    expect($resume->selectionFor(ResumeList::Experience))->toBe([['id' => $experience->id, 'included' => true]])
        ->and($resume->textOverride('headline'))->toBe('Staff Engineer')
        ->and($resume->textOverride('summary'))->toBeNull()
        ->and($resume->highlightsOverride($experience->id))->toBe(['Cut costs by 20%'])
        ->and($resume->overrides['headline']['base_hash'])->toBe(Resume::baseHash('Engineer — Backend'));
    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()->addMinutes(5)));
});

test('an override stays marked as stale until the owner edits or reviews it', function (): void {
    $resume = Resume::factory()->create(['overrides' => ['headline' => ['text' => 'Staff Engineer', 'base_hash' => Resume::baseHash('Old tagline')]]]);
    $this->actingAs($this->owner);

    $this->put(route('resumes.update', $resume), resumeForm($resume, ['overrides' => ['headline' => 'Staff Engineer']]));
    expect($resume->fresh()->overrides['headline']['base_hash'])->toBe(Resume::baseHash('Old tagline'));

    $this->put(route('resumes.update', $resume), resumeForm($resume, ['overrides' => ['headline' => 'Staff Engineer'], 'reviewed_overrides' => ['headline']]));
    expect($resume->fresh()->overrides['headline']['base_hash'])->toBe(Resume::baseHash('Engineer — Backend'));
});

test('a new design rebuilds the pdf right away', function (string $field, string $value): void {
    $resume = Resume::public();
    $this->freezeTime();

    $this->actingAs($this->owner)->put(route('resumes.update', $resume), resumeForm($resume, [$field => $value]))->assertSessionHasNoErrors();

    expect(Resume::public()->design()[$field])->toBe($value);
    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()));
})->with([
    'template' => ['template', 'sidebar'],
    'colour' => ['accent', 'burgundy'],
    'font' => ['font', 'instrument'],
]);

test('an unset colour is emerald and an unset font follows the template', function (): void {
    $resume = Resume::factory()->create(['template' => ResumeTemplate::Sidebar]);

    expect($resume->design())->toBe(['template' => 'sidebar', 'accent' => 'emerald', 'accentColor' => ResumeAccent::Emerald->color(), 'font' => 'poppins']);

    $resume->update(['accent' => ResumeAccent::Blue, 'font' => ResumeFont::Arial]);

    expect($resume->design())->toMatchArray(['accent' => 'blue', 'font' => 'arial']);
});

test('the settings must be valid', function (array $input, string $field): void {
    $resume = Resume::public();

    $this->actingAs($this->owner)->put(route('resumes.update', $resume), resumeForm($resume, $input))->assertSessionHasErrors($field);

    expect(Resume::public()->template)->toBe(ResumeTemplate::Classic);
})->with([
    'unknown template' => [['template' => 'fancy'], 'template'],
    'unknown colour' => [['accent' => 'neon'], 'accent'],
    'unknown font' => [['font' => 'comic'], 'font'],
    'unknown list' => [['selection' => ['testimonials' => []]], 'selection'],
    'unknown override' => [['reviewed_overrides' => ['tagline']], 'reviewed_overrides.0'],
]);

test('making a resume public moves the public resume to it', function (): void {
    $resume = Resume::factory()->create(['archived_at' => now()]);

    $this->actingAs($this->owner)->post(route('resumes.publish', $resume))->assertRedirect();

    expect(Resume::public()->id)->toBe($resume->id)
        ->and($resume->fresh()->archived_at)->toBeNull()
        ->and(Resume::query()->where('is_public', true)->count())->toBe(1);
});

test('a resume is deleted with its pdfs, unless one was sent with a job and it is archived instead', function (): void {
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/unsent.pdf', 'pdf');
    $unsent = Resume::factory()->create();
    ResumeBuild::factory()->ready()->create(['resume_id' => $unsent->id, 'path' => 'resume-builds/unsent.pdf']);
    $sent = Resume::factory()->create();
    JobApplication::factory()->create(['resume_build_id' => ResumeBuild::factory()->ready()->create(['resume_id' => $sent->id])->id]);
    $this->actingAs($this->owner);

    $this->delete(route('resumes.destroy', $unsent))->assertRedirect(route('resumes.index'));
    $this->delete(route('resumes.destroy', $sent));

    expect(Resume::query()->whereKey($unsent->id)->exists())->toBeFalse()
        ->and($sent->fresh()->archived_at)->not->toBeNull();
    Storage::disk(ResumeBuild::DISK)->assertMissing('resume-builds/unsent.pdf');
});

test('the public resume cannot be deleted', function (): void {
    $this->actingAs($this->owner)->delete(route('resumes.destroy', Resume::public()));

    expect(Resume::public()->exists)->toBeTrue();
});

test('the owner can download any build', function (): void {
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/private.pdf', 'private');
    $build = ResumeBuild::factory()->ready()->create(['resume_id' => Resume::factory()->create()->id, 'path' => 'resume-builds/private.pdf']);

    $this->actingAs($this->owner)->get(route('resumes.builds.download', $build))->assertOk()->assertDownload();
    auth()->logout();
    $this->get(route('resumes.builds.download', $build))->assertRedirect(route('login'));
});

test('guests cannot reach the resume pages', function (): void {
    $resume = Resume::public();

    $this->get(route('resumes.index'))->assertRedirect(route('login'));
    $this->get(route('resumes.edit', $resume))->assertRedirect(route('login'));
    $this->put(route('resumes.update', $resume), resumeForm($resume, ['template' => 'compact']))->assertRedirect(route('login'));
    $this->post(route('resumes.preview', $resume), resumeForm($resume))->assertRedirect(route('login'));
    $this->post(route('resumes.store'), ['name' => 'Sneaky'])->assertRedirect(route('login'));

    expect(Resume::public()->template)->toBe(ResumeTemplate::Classic)->and(Resume::query()->count())->toBe(1);
});

test('the preview renders unsaved changes without saving them', function (): void {
    $kept = Experience::factory()->create(['role' => 'Kept', 'highlights' => ['Shipped A']]);
    $dropped = Experience::factory()->create(['role' => 'Dropped']);
    $resume = Resume::factory()->create(['selection' => ['experience' => [['id' => $kept->id, 'included' => true], ['id' => $dropped->id, 'included' => true]]]]);

    $this->actingAs($this->owner)->postJson(route('resumes.preview', $resume), resumeForm($resume, [
        'template' => 'sidebar',
        'accent' => 'blue',
        'selection' => ['experience' => [['id' => $kept->id, 'included' => true], ['id' => $dropped->id, 'included' => false]]],
        'overrides' => ['headline' => 'Staff Engineer', 'highlights' => [$kept->id => ['Cut costs by 20%']]],
    ]))->assertOk()
        ->assertJsonPath('design.template', 'sidebar')
        ->assertJsonPath('design.accentColor', ResumeAccent::Blue->color())
        ->assertJsonPath('design.font', 'poppins')
        ->assertJsonPath('portfolio.tagline', 'Staff Engineer')
        ->assertJsonCount(1, 'portfolio.experience')
        ->assertJsonPath('portfolio.experience.0.highlights', ['Cut costs by 20%']);

    $resume->refresh();
    expect($resume->template)->toBe(ResumeTemplate::Classic)
        ->and($resume->overrides)->toBeNull();
    Bus::assertNotDispatched(RebuildResumePdf::class);
});

test('the preview rejects what saving would reject', function (): void {
    $resume = Resume::public();

    $this->actingAs($this->owner)->postJson(route('resumes.preview', $resume), resumeForm($resume, ['template' => 'fancy']))->assertJsonValidationErrors('template');
});

test('applying to a job records the current pdf of the resume tailored for it', function (): void {
    $application = JobApplication::factory()->wishlist()->create();
    $tailored = Resume::factory()->create(['job_application_id' => $application->id]);
    $build = ResumeBuild::factory()->ready()->create(['resume_id' => $tailored->id]);
    ResumeBuild::factory()->ready()->create();

    $this->actingAs($this->owner)->put(route('job-hunt.applications.status.update', [$application->hunt, $application]), ['status' => JobApplicationStatus::Applied->value]);

    expect($application->fresh()->resume_build_id)->toBe($build->id);
});

test('a job added as applied records the public resume pdf', function (): void {
    $build = ResumeBuild::factory()->ready()->create();
    $hunt = App\Models\JobHunt::factory()->create();

    $this->actingAs($this->owner)->post(route('job-hunt.applications.store', $hunt), ['company' => 'Acme', 'role' => 'Engineer', 'status' => 'applied']);

    expect(JobApplication::query()->sole()->resume_build_id)->toBe($build->id);
});

test('the owner can change the pdf a job was sent with', function (): void {
    $application = JobApplication::factory()->create();
    $build = ResumeBuild::factory()->ready()->create();
    $scheduled = ResumeBuild::factory()->create();
    $this->actingAs($this->owner);

    $this->put(route('job-hunt.applications.sent-resume.update', [$application->hunt, $application]), ['resume_build_id' => $scheduled->id])->assertSessionHasErrors('resume_build_id');
    $this->put(route('job-hunt.applications.sent-resume.update', [$application->hunt, $application]), ['resume_build_id' => $build->id])->assertSessionHasNoErrors();

    expect($application->fresh()->resume_build_id)->toBe($build->id)
        ->and($build->fresh()->status)->toBe(ResumePdfStatus::Ready);
});

test('an archived resume is never rebuilt', function (): void {
    $resume = Resume::factory()->create(['archived_at' => now()]);
    $this->actingAs($this->owner);

    $this->put(route('resumes.update', $resume), resumeForm($resume, ['template' => 'compact']));
    $this->post(route('resumes.rebuild', $resume));

    Bus::assertNotDispatched(RebuildResumePdf::class);
});

test('applying to a job falls back to the public pdf while the tailored resume has none', function (): void {
    $application = JobApplication::factory()->wishlist()->create();
    Resume::factory()->create(['job_application_id' => $application->id]);
    $public = ResumeBuild::factory()->ready()->create();

    $this->actingAs($this->owner)->put(route('job-hunt.applications.status.update', [$application->hunt, $application]), ['status' => JobApplicationStatus::Applied->value]);

    expect($application->fresh()->resume_build_id)->toBe($public->id);
});
