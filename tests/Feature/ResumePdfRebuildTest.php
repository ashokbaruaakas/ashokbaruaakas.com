<?php

declare(strict_types=1);

use App\Actions\Portfolio\BuildResumePdfAction;
use App\Actions\Portfolio\GetResumePdfStatusAction;
use App\Enums\ResumePdfStatus;
use App\Jobs\RebuildResumePdf;
use App\Models\JobApplication;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\ResumeBuild;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake(ResumeBuild::DISK);
    config(['services.chrome.path' => PHP_BINARY]);
    $this->owner = User::factory()->create(['name' => 'Ada Lovelace']);
    Profile::factory()->create(['name' => 'Ada Lovelace', 'email' => $this->owner->email, 'professional_summary' => 'Writes programs.']);
});

function fakeBrowserWritingPdf(int $bytes = 30 * 1024, ?Closure $afterRender = null, ?string $pageTree = null): void
{
    Process::fake(function (PendingProcess $process) use ($bytes, $afterRender, $pageTree): ProcessResult {
        $outputArgument = collect($process->command)->first(fn (string $argument): bool => str_starts_with($argument, '--print-to-pdf='));
        $pageTree ??= "39 0 obj\n<</Type /Pages\n/Count 2\n/Kids [2 0 R 23 0 R]>>\nendobj\n";

        file_put_contents(mb_substr($outputArgument, mb_strlen('--print-to-pdf=')), $pageTree . str_repeat('x', max(0, $bytes - mb_strlen($pageTree))));

        if ($afterRender instanceof Closure) {
            $afterRender();
        }

        return Process::result();
    });
}

function runRebuildJob(string $token): void
{
    app()->call([new RebuildResumePdf($token), 'handle']);
}

function scheduledBuild(?Resume $resume = null): ResumeBuild
{
    return ResumeBuild::factory()->create(['resume_id' => ($resume ?? Resume::public())->id]);
}

test('editing the portfolio schedules a rebuild five minutes later', function (Closure $edit): void {
    $this->freezeTime();
    $this->actingAs($this->owner);

    $edit()->assertSessionHasNoErrors()->assertRedirect();

    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()->addMinutes(5)));
    expect(ResumeBuild::query()->sole())
        ->status->toBe(ResumePdfStatus::Scheduled)
        ->scheduled_for->toDateTimeString()->toBe(now()->addMinutes(5)->toDateTimeString());
})->with([
    'profile' => fn () => test()->put(route('portfolio.profile.update'), ['name' => 'Renamed']),
    'copy' => fn () => test()->put(route('portfolio.copy.update'), [
        'sections' => ['about' => ['title' => 'About']],
        'pages' => ['resume' => ['title' => 'Resume']],
    ]),
    'new list item' => fn () => test()->post(route('portfolio.lists.store', 'experience'), [
        'role' => 'Engineer',
        'company' => 'Acme',
        'period' => '2024',
        'highlights' => [],
    ]),
    'removed list item' => fn () => test()->delete(route('portfolio.lists.destroy', ['list' => 'languages', 'item' => Language::factory()->create()->id])),
    'reorder' => fn () => test()->put(route('portfolio.lists.order.update', 'languages'), ['ids' => [Language::factory()->create()->id]]),
    'hidden list item' => fn () => test()->put(route('portfolio.lists.visibility.update', ['list' => 'testimonials', 'item' => Testimonial::factory()->create()->id]), ['is_visible' => false]),
    'removed image' => fn () => test()->delete(route('portfolio.media.destroy', 'avatar')),
    'account details synced to the profile' => fn () => test()->patch('/account', ['name' => 'Renamed', 'email' => Profile::query()->value('email')]),
]);

test('uploading an image schedules a rebuild', function (): void {
    $this->actingAs($this->owner)->post(route('portfolio.media.update', 'avatar'), ['file' => UploadedFile::fake()->image('me.png', 200, 200)])->assertRedirect();

    Bus::assertDispatchedTimes(RebuildResumePdf::class, 1);
});

test('a failed validation does not schedule a rebuild', function (): void {
    $this->actingAs($this->owner)->post(route('portfolio.media.update', 'avatar'), [])->assertSessionHasErrors('file');

    Bus::assertNotDispatched(RebuildResumePdf::class);
    expect(ResumeBuild::query()->exists())->toBeFalse();
});

test('an edit schedules a build of every resume that is not archived', function (): void {
    $private = Resume::factory()->create();
    Resume::factory()->create(['archived_at' => now()]);

    $this->actingAs($this->owner)->delete(route('portfolio.media.destroy', 'avatar'));

    expect(ResumeBuild::query()->pluck('resume_id')->sort()->values()->all())->toBe([Resume::public()->id, $private->id]);
});

test('a new change restarts the wait and supersedes the earlier build', function (): void {
    fakeBrowserWritingPdf();
    $this->freezeTime();
    $this->actingAs($this->owner);

    $this->delete(route('portfolio.media.destroy', 'avatar'));
    $firstToken = ResumeBuild::query()->sole()->token;

    $this->travel(3)->minutes();
    $this->delete(route('portfolio.media.destroy', 'avatar'));

    $build = ResumeBuild::query()->sole();
    expect($build->token)->not->toBe($firstToken)
        ->and($build->scheduled_for->toDateTimeString())->toBe(now()->addMinutes(5)->toDateTimeString());

    runRebuildJob($firstToken);

    Process::assertNothingRan();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Scheduled);
});

test('the job prints the resume from a signed link and stores the pdf and its page count', function (string $pageTree): void {
    fakeBrowserWritingPdf(pageTree: $pageTree);
    $build = scheduledBuild();

    runRebuildJob($build->token);

    $build->refresh();
    Storage::disk(ResumeBuild::DISK)->assertExists($build->path);
    expect($build->status)->toBe(ResumePdfStatus::Ready)
        ->and($build->page_count)->toBe(3)
        ->and($build->snapshot)->toContain('Writes programs.')
        ->and(Resume::public()->page_count)->toBe(3);
    Process::assertRan(function (PendingProcess $process): bool {
        $url = collect($process->command)->last();

        return str_contains($url, '/resumes/main/print') && str_contains($url, 'signature=');
    });
})->with([
    'as chrome writes it' => "39 0 obj\n<</Type /Pages\n/Count 3\n/Kids [2 0 R 23 0 R 37 0 R]>>\nendobj\n",
    'count before type, beside a nested page node' => "5 0 obj\n<</Count 3 /Kids [6 0 R 7 0 R] /Type /Pages>>\nendobj\n7 0 obj\n<</Type /Pages /Parent 5 0 R /Count 2 /Kids [8 0 R 9 0 R]>>\nendobj\n",
]);

test('a build with the same content as the current pdf is dropped', function (): void {
    fakeBrowserWritingPdf();
    runRebuildJob(scheduledBuild()->token);
    $current = Resume::public()->currentBuild();

    $again = scheduledBuild();
    runRebuildJob($again->token);

    expect(ResumeBuild::query()->whereKey($again->id)->exists())->toBeFalse()
        ->and(Resume::public()->currentBuild()->id)->toBe($current->id);
    Process::assertRanTimes(fn (): bool => true, 1);
});

test('a change in content adds a new build to the history', function (): void {
    fakeBrowserWritingPdf();
    runRebuildJob(scheduledBuild()->token);

    Profile::query()->update(['professional_summary' => 'Writes better programs.']);
    runRebuildJob(scheduledBuild()->token);

    expect(Resume::public()->builds()->where('status', ResumePdfStatus::Ready)->count())->toBe(2);
});

test('the history keeps the latest unsent builds and every build sent with a job application', function (): void {
    fakeBrowserWritingPdf();
    $sent = ResumeBuild::factory()->ready()->create(['built_at' => now()->subYear()]);
    JobApplication::factory()->create(['resume_build_id' => $sent->id]);
    ResumeBuild::factory()->ready()->count(BuildResumePdfAction::KEPT_UNSENT_BUILDS)->create(['built_at' => now()->subDay()]);
    $oldest = ResumeBuild::factory()->ready()->create(['built_at' => now()->subMonth()]);

    runRebuildJob(scheduledBuild()->token);

    expect(ResumeBuild::query()->whereKey($sent->id)->exists())->toBeTrue()
        ->and(ResumeBuild::query()->whereKey($oldest->id)->exists())->toBeFalse()
        ->and(Resume::public()->builds()->where('status', ResumePdfStatus::Ready)->count())->toBe(BuildResumePdfAction::KEPT_UNSENT_BUILDS + 1);
});

test('a change made during a build keeps the new build scheduled', function (): void {
    $build = scheduledBuild();
    fakeBrowserWritingPdf(afterRender: fn () => resolve(App\Actions\Portfolio\ScheduleResumePdfRebuildAction::class)->handle());

    runRebuildJob($build->token);

    expect($build->fresh()->status)->toBe(ResumePdfStatus::Ready)
        ->and(ResumeBuild::query()->where('status', ResumePdfStatus::Scheduled)->count())->toBe(1);
});

test('a blank render fails without replacing the pdf', function (): void {
    fakeBrowserWritingPdf(bytes: 1024);
    $build = scheduledBuild();

    expect(fn () => runRebuildJob($build->token))->toThrow(RuntimeException::class, 'rendered blank');
    expect(Resume::public()->currentBuild())->toBeNull();
});

test('a browser error fails the job', function (): void {
    Process::fake(fn (): ProcessResult => Process::result(exitCode: 1));
    $build = scheduledBuild();

    expect(fn () => runRebuildJob($build->token))->toThrow(RuntimeException::class);
});

test('a failed build retries within seconds instead of waiting out the scheduling delay', function (): void {
    Queue::connection('database')->later(now()->addMinutes(5), new RebuildResumePdf('token'));

    $job = new DatabaseJob(app(), Queue::connection('database'), DB::table('jobs')->sole(), 'database', 'default');

    expect($job->backoff())->toBe('1,5,10');
});

test('a job out of attempts marks its own build as failed and keeps only the latest failure', function (): void {
    $earlier = ResumeBuild::factory()->create(['status' => ResumePdfStatus::Failed]);
    $build = ResumeBuild::factory()->create(['status' => ResumePdfStatus::Building]);

    new RebuildResumePdf('unknown')->failed();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Building);

    new RebuildResumePdf($build->token)->failed();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Failed)
        ->and(ResumeBuild::query()->whereKey($earlier->id)->exists())->toBeFalse();
});

test('the unsent pdfs and the page count are removed when the resume page no longer exists', function (): void {
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/old.pdf', 'pdf');
    ResumeBuild::factory()->ready()->create(['path' => 'resume-builds/old.pdf']);
    Resume::public()->update(['page_count' => 3]);
    Profile::query()->update(['professional_summary' => null]);
    $build = scheduledBuild();
    Process::fake();

    runRebuildJob($build->token);

    Process::assertNothingRan();
    Storage::disk(ResumeBuild::DISK)->assertMissing('resume-builds/old.pdf');
    expect(ResumeBuild::query()->exists())->toBeFalse()
        ->and(Resume::public()->page_count)->toBeNull();
});

test('the print page needs a valid signature', function (): void {
    $private = Resume::factory()->create(['slug' => 'backend']);

    $this->get(route('resumes.print', 'backend'))->assertForbidden();

    $this->get(URL::temporarySignedRoute('resumes.print', now()->addMinute(), ['resume' => 'backend'], absolute: false))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('resume/Print')->where('template', $private->template->value));
});

test('the owner can rebuild the public resume right away', function (): void {
    $this->freezeTime();

    $this->actingAs($this->owner)->from('/dashboard')->post(route('portfolio.resume-pdf.rebuild'))->assertRedirect('/dashboard');

    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()));
});

test('guests cannot rebuild', function (): void {
    $this->post(route('portfolio.resume-pdf.rebuild'))->assertRedirect(route('login'));

    Bus::assertNotDispatched(RebuildResumePdf::class);
});

test('dashboard pages share the rebuild status', function (): void {
    $this->freezeTime();
    $build = scheduledBuild();

    $this->actingAs($this->owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('resumePdf.status', 'scheduled')
        ->where('resumePdf.scheduledFor', $build->scheduled_for->toIso8601String()));
});

test('public pages do not share the rebuild status', function (): void {
    scheduledBuild();

    $this->get(route('resume'))->assertInertia(fn (Assert $page) => $page->where('resumePdf', null));
});

test('the status reports a missing pdf only when there is a resume to build', function (): void {
    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBe(['status' => 'missing', 'scheduledFor' => null, 'pageCount' => null, 'pageLimit' => 2]);

    Profile::query()->update(['professional_summary' => null]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the status is quiet once the pdf is ready', function (): void {
    ResumeBuild::factory()->ready()->create();

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the status warns when the built pdf runs over the page limit', function (): void {
    ResumeBuild::factory()->ready()->create();
    Resume::public()->update(['page_count' => 3, 'page_limit' => 2]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBe(['status' => 'over_page_limit', 'scheduledFor' => null, 'pageCount' => 3, 'pageLimit' => 2]);

    Resume::public()->update(['page_count' => 2]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the public pdf is the public resume current build, under the owner name', function (): void {
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/old.pdf', 'old');
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/new.pdf', 'new');
    ResumeBuild::factory()->ready()->create(['path' => 'resume-builds/old.pdf', 'built_at' => now()->subDay()]);
    ResumeBuild::factory()->ready()->create(['path' => 'resume-builds/new.pdf']);

    $response = $this->get(route('resume.pdf'))->assertOk()->assertDownload('Ada-Lovelace-Resume.pdf');

    expect($response->streamedContent())->toBe('new');
});

test('a newly public resume serves the previous public pdf until its own is built, and never a private one', function (): void {
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/main.pdf', 'main');
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/private.pdf', 'private');
    ResumeBuild::factory()->ready()->create(['path' => 'resume-builds/main.pdf']);
    ResumeBuild::factory()->ready()->create(['resume_id' => Resume::factory()->create()->id, 'path' => 'resume-builds/private.pdf', 'built_at' => now()->addMinute()]);
    $next = Resume::factory()->create();

    $this->actingAs($this->owner)->post(route('portfolio.resumes.publish', $next));
    auth()->logout();

    expect($this->get(route('resume.pdf'))->assertOk()->streamedContent())->toBe('main');
});

test('the pdf is not found before it has been built', function (): void {
    $this->get(route('resume.pdf'))->assertNotFound();
});

test('public pages know whether the public pdf exists', function (): void {
    $this->get(route('resume'))->assertInertia(fn (Assert $page) => $page->where('hasResumePdf', false));

    ResumeBuild::factory()->ready()->create();

    $this->get(route('resume'))->assertInertia(fn (Assert $page) => $page->where('hasResumePdf', true));
});
