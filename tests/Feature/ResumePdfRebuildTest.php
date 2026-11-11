<?php

declare(strict_types=1);

use App\Actions\Portfolio\GetResumePdfStatusAction;
use App\Enums\MediaKey;
use App\Enums\ResumePdfStatus;
use App\Jobs\RebuildResumePdf;
use App\Models\Language;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\ResumePdfBuild;
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
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Storage::fake('public');
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

test('editing the portfolio schedules a rebuild five minutes later', function (Closure $edit): void {
    $this->freezeTime();
    $this->actingAs($this->owner);

    $edit()->assertSessionHasNoErrors()->assertRedirect();

    Bus::assertDispatched(RebuildResumePdf::class, fn (RebuildResumePdf $job): bool => $job->delay->equalTo(now()->addMinutes(5)));
    expect(ResumePdfBuild::query()->sole())
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
    expect(ResumePdfBuild::query()->exists())->toBeFalse();
});

test('a new change restarts the wait and supersedes the earlier job', function (): void {
    fakeBrowserWritingPdf();
    $this->freezeTime();
    $this->actingAs($this->owner);

    $this->delete(route('portfolio.media.destroy', 'avatar'));
    $firstToken = ResumePdfBuild::query()->sole()->token;

    $this->travel(3)->minutes();
    $this->delete(route('portfolio.media.destroy', 'avatar'));

    $build = ResumePdfBuild::query()->sole();
    expect($build->token)->not->toBe($firstToken)
        ->and($build->scheduled_for->toDateTimeString())->toBe(now()->addMinutes(5)->toDateTimeString());

    runRebuildJob($firstToken);

    Process::assertNothingRan();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Scheduled);
});

test('the job renders the resume and stores the pdf under the owner name', function (): void {
    fakeBrowserWritingPdf();
    $build = ResumePdfBuild::factory()->create();

    runRebuildJob($build->token);

    $media = Media::query()->where('key', MediaKey::ResumePdf)->sole();
    Storage::disk('public')->assertExists($media->path);
    expect($media->original_name)->toBe('Ada-Lovelace-Resume.pdf')
        ->and($build->fresh()->status)->toBe(ResumePdfStatus::Ready);
    Process::assertRan(fn (PendingProcess $process): bool => in_array(route('resume', ['build' => $build->token]), $process->command, true));
});

test('the job stores the page count of the built pdf on the public resume', function (string $pageTree): void {
    fakeBrowserWritingPdf(pageTree: $pageTree);
    $build = ResumePdfBuild::factory()->create();

    runRebuildJob($build->token);

    expect(Resume::public()->page_count)->toBe(3);
})->with([
    'as chrome writes it' => "39 0 obj\n<</Type /Pages\n/Count 3\n/Kids [2 0 R 23 0 R 37 0 R]>>\nendobj\n",
    'count before type, beside a nested page node' => "5 0 obj\n<</Count 3 /Kids [6 0 R 7 0 R] /Type /Pages>>\nendobj\n7 0 obj\n<</Type /Pages /Parent 5 0 R /Count 2 /Kids [8 0 R 9 0 R]>>\nendobj\n",
]);

test('a change made during a build keeps the new rebuild scheduled', function (): void {
    $build = ResumePdfBuild::factory()->create();
    fakeBrowserWritingPdf(afterRender: fn () => ResumePdfBuild::query()->whereKey($build->id)->update(['token' => 'newer', 'status' => ResumePdfStatus::Scheduled]));

    runRebuildJob($build->token);

    expect($build->fresh()->status)->toBe(ResumePdfStatus::Scheduled);
});

test('a blank render fails without replacing the pdf', function (): void {
    fakeBrowserWritingPdf(bytes: 1024);
    $build = ResumePdfBuild::factory()->create();

    expect(fn () => runRebuildJob($build->token))->toThrow(RuntimeException::class, 'rendered blank');
    expect(Media::query()->where('key', MediaKey::ResumePdf)->exists())->toBeFalse();
});

test('a browser error fails the job', function (): void {
    Process::fake(fn (): ProcessResult => Process::result(exitCode: 1));
    $build = ResumePdfBuild::factory()->create();

    expect(fn () => runRebuildJob($build->token))->toThrow(RuntimeException::class);
});

test('a failed build retries within seconds instead of waiting out the scheduling delay', function (): void {
    Queue::connection('database')->later(now()->addMinutes(5), new RebuildResumePdf('token'));

    $job = new DatabaseJob(app(), Queue::connection('database'), DB::table('jobs')->sole(), 'database', 'default');

    expect($job->backoff())->toBe('1,5,10');
});

test('a job out of attempts marks only its own rebuild as failed', function (): void {
    $build = ResumePdfBuild::factory()->create(['status' => ResumePdfStatus::Building]);

    new RebuildResumePdf('older')->failed();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Building);

    new RebuildResumePdf($build->token)->failed();
    expect($build->fresh()->status)->toBe(ResumePdfStatus::Failed);
});

test('the stored pdf is removed when the resume page no longer exists', function (): void {
    Storage::disk('public')->put('owner/resume.pdf', 'pdf');
    Media::factory()->create(['key' => MediaKey::ResumePdf, 'path' => 'owner/resume.pdf']);
    Profile::query()->update(['professional_summary' => null]);
    $build = ResumePdfBuild::factory()->create();
    Process::fake();

    runRebuildJob($build->token);

    Process::assertNothingRan();
    Storage::disk('public')->assertMissing('owner/resume.pdf');
    expect(Media::query()->where('key', MediaKey::ResumePdf)->exists())->toBeFalse();
});

test('the page count is cleared when the resume page no longer exists', function (): void {
    Resume::public()->update(['page_count' => 3]);
    Profile::query()->update(['professional_summary' => null]);
    $build = ResumePdfBuild::factory()->create();
    Process::fake();

    runRebuildJob($build->token);

    expect(Resume::public()->page_count)->toBeNull();
});

test('the owner can rebuild right away', function (): void {
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
    $build = ResumePdfBuild::factory()->create();

    $this->actingAs($this->owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('resumePdf.status', 'scheduled')
        ->where('resumePdf.scheduledFor', $build->scheduled_for->toIso8601String()));
});

test('public pages do not share the rebuild status', function (): void {
    ResumePdfBuild::factory()->create();

    $this->get(route('resume'))->assertInertia(fn (Assert $page) => $page->where('resumePdf', null));
});

test('the status reports a missing pdf only when there is a resume to build', function (): void {
    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBe(['status' => 'missing', 'scheduledFor' => null, 'pageCount' => null, 'pageLimit' => 2]);

    Profile::query()->update(['professional_summary' => null]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the status is quiet once the pdf is ready', function (): void {
    ResumePdfBuild::factory()->create(['status' => ResumePdfStatus::Ready]);
    Media::factory()->create(['key' => MediaKey::ResumePdf]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the status warns when the built pdf runs over the page limit', function (): void {
    ResumePdfBuild::factory()->create(['status' => ResumePdfStatus::Ready]);
    Media::factory()->create(['key' => MediaKey::ResumePdf]);
    Resume::public()->update(['page_count' => 3, 'page_limit' => 2]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBe(['status' => 'over_page_limit', 'scheduledFor' => null, 'pageCount' => 3, 'pageLimit' => 2]);

    Resume::public()->update(['page_count' => 2]);

    expect(resolve(GetResumePdfStatusAction::class)->handle())->toBeNull();
});

test('the pdf downloads under the owner name', function (): void {
    Storage::disk('public')->put('owner/resume.pdf', 'pdf');
    Media::factory()->create(['key' => MediaKey::ResumePdf, 'path' => 'owner/resume.pdf', 'original_name' => 'Ada-Lovelace-Resume.pdf']);

    $this->get(route('resume.pdf'))->assertOk()->assertDownload('Ada-Lovelace-Resume.pdf');
});

test('the pdf is not found before it has been built', function (): void {
    $this->get(route('resume.pdf'))->assertNotFound();
});
