<?php

declare(strict_types=1);

use App\Enums\AiProvider;
use App\Enums\JobApplicationStatus;
use App\Enums\MediaKey;
use App\Jobs\RebuildResumePdf;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobHunt;
use App\Models\Media;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use App\Models\ResumeBuild;
use App\Models\SkillCategory;
use App\Models\User;
use App\Settings\AiSettings;
use App\Settings\MailSettings;
use Database\Seeders\AshokBaruaSeeder;
use Database\Seeders\DummySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Storage::fake('public');
    $this->owner = User::factory()->create();
    $this->actingAs($this->owner);
});

/**
 * @return array<string, string>
 */
function readBackupZip(string $path): array
{
    $zip = new ZipArchive();
    $zip->open($path);
    $entries = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entries[(string) $zip->getNameIndex($index)] = (string) $zip->getFromIndex($index);
    }

    $zip->close();

    return $entries;
}

/**
 * @return array<string, string>
 */
function downloadBackup(): array
{
    $path = test()->get(route('settings.backup.download'))->assertOk()->baseResponse->getFile()->getPathname();
    $entries = readBackupZip($path);

    unlink($path);

    return $entries;
}

/**
 * @param  array<string, string>  $entries
 */
function zipUpload(array $entries): UploadedFile
{
    $path = (string) tempnam(sys_get_temp_dir(), 'backup-test-');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::OVERWRITE);

    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
}

/**
 * @param  array<string, mixed>  $content
 * @param  array<string, string>  $files
 * @param  array<string, mixed>  $manifest
 * @return array<string, string>
 */
function backupEntries(array $content = [], array $files = [], array $manifest = []): array
{
    $entries = ['content.json' => json_encode([
        'profile' => null,
        'lists' => array_fill_keys(array_column(App\Enums\PortfolioList::cases(), 'value'), []),
        'sections' => [],
        'pages' => [],
        'settings' => [
            'authentication' => ['requireEmailVerification' => true],
            'mail' => ['fromName' => null, 'fromAddress' => null],
        ],
        'media' => [],
        ...$content,
    ], JSON_THROW_ON_ERROR), ...$files];

    return [
        'manifest.json' => json_encode([
            'format' => 'portfolio-backup',
            'version' => 1,
            'checksums' => array_map(fn (string $contents): string => hash('sha256', $contents), $entries),
            ...$manifest,
        ], JSON_THROW_ON_ERROR),
        ...$entries,
    ];
}

function postBackup(UploadedFile $file): TestResponse
{
    return test()->from(route('settings.backup.show'))->post(route('settings.backup.store'), ['file' => $file]);
}

function pngContents(): string
{
    return UploadedFile::fake()->image('image.png', 4, 4)->getContent();
}

test('guests cannot reach the backup routes', function (string $method, string $route): void {
    auth()->logout();

    $this->call($method, route($route))->assertRedirect(route('login'));
})->with([
    'page' => ['GET', 'settings.backup.show'],
    'export' => ['GET', 'settings.backup.download'],
    'import' => ['POST', 'settings.backup.store'],
]);

test('the page renders', function (): void {
    $this->get(route('settings.backup.show'))->assertInertia(fn (Assert $page) => $page->component('settings/Backup'));
});

test('an export holds the content and files but no account details', function (): void {
    Profile::factory()->create(['name' => 'Ada Lovelace', 'email' => 'public@example.com']);
    Storage::disk('public')->put('owner/avatar.webp', 'avatar bytes');
    Media::factory()->create(['key' => MediaKey::Avatar, 'path' => 'owner/avatar.webp', 'original_name' => 'me.webp']);

    $this->get(route('settings.backup.download'))->assertDownload();

    $entries = downloadBackup();
    $content = json_decode($entries['content.json'], true);
    $manifest = json_decode($entries['manifest.json'], true);

    expect($entries['files/avatar.webp'])->toBe('avatar bytes')
        ->and($content['profile']['name'])->toBe('Ada Lovelace')
        ->and($content['media'])->toBe([['key' => 'avatar', 'file' => 'files/avatar.webp', 'original_name' => 'me.webp']])
        ->and($manifest['checksums']['files/avatar.webp'])->toBe(hash('sha256', 'avatar bytes'))
        ->and($entries['content.json'])->not->toContain($this->owner->email)
        ->and($entries['content.json'])->not->toContain('password');
});

test('an export fails with a message when a stored file is missing', function (): void {
    Media::factory()->create(['key' => MediaKey::Avatar, 'path' => 'owner/missing.webp']);

    $this->from(route('settings.backup.show'))
        ->get(route('settings.backup.download'))
        ->assertRedirect(route('settings.backup.show'));
});

test('a seeded site survives an export and import unchanged', function (string $seeder): void {
    $this->seed($seeder);

    $before = downloadBackup();
    $listCounts = json_decode($before['content.json'], true)['lists'];

    expect(array_filter($listCounts))->not->toBeEmpty();

    postBackup(zipUpload($before))->assertSessionHasNoErrors()->assertRedirect();

    $after = downloadBackup();
    unset($before['manifest.json'], $after['manifest.json']);

    expect($after)->toBe($before);

    foreach (Media::query()->get() as $media) {
        Storage::disk('public')->assertExists($media->path);
    }
})->with([
    'dummy site' => [DummySeeder::class],
    'owner site' => [AshokBaruaSeeder::class],
]);

test('an import replaces the content, files and settings', function (): void {
    $project = Project::factory()->create(['name' => 'Kept', 'is_visible' => false, 'sort_order' => 5]);
    $project->links()->create(['label' => 'Docs', 'url' => 'https://example.com/docs', 'sort_order' => 0]);
    Project::factory()->create(['name' => 'Second', 'sort_order' => 6]);
    Storage::disk('public')->put('owner/avatar-new.png', pngContents());
    Media::factory()->create(['key' => MediaKey::Avatar, 'path' => 'owner/avatar-new.png', 'mime_type' => 'image/png']);
    Profile::factory()->create(['name' => 'Ada Lovelace']);
    PortfolioSection::factory()->create(['key' => 'about', 'title' => 'About']);
    PageContent::factory()->create(['key' => 'home', 'title' => 'Home']);
    resolve(App\Settings\SettingsRepository::class)->save(new MailSettings()->fill(['fromName' => 'Restored']));

    $backup = zipUpload(downloadBackup());

    Project::query()->delete();
    SkillCategory::factory()->create();
    Profile::query()->update(['name' => 'Changed']);
    Storage::disk('public')->put('owner/old-photo.jpg', 'old');
    Media::factory()->create(['key' => MediaKey::Photo, 'path' => 'owner/old-photo.jpg']);
    resolve(App\Settings\SettingsRepository::class)->save(new MailSettings()->fill(['fromName' => 'Changed']));
    $accountPassword = $this->owner->fresh()->password;

    postBackup($backup)->assertSessionHasNoErrors()->assertRedirect(route('settings.backup.show'));

    $projects = Project::query()->with('links')->orderBy('sort_order')->get();
    $avatar = Media::query()->sole();

    expect($projects->pluck('name')->all())->toBe(['Kept', 'Second'])
        ->and($projects->pluck('sort_order')->all())->toBe([0, 1])
        ->and($projects[0]->is_visible)->toBeFalse()
        ->and($projects[0]->links->sole()->url)->toBe('https://example.com/docs')
        ->and(SkillCategory::query()->exists())->toBeFalse()
        ->and(Profile::query()->sole()->name)->toBe('Ada Lovelace')
        ->and(resolve(MailSettings::class)->fromName)->toBe('Restored')
        ->and($avatar->key)->toBe(MediaKey::Avatar)
        ->and($avatar->path)->toStartWith('owner/avatar-')
        ->and($avatar->mime_type)->toBe('image/png')
        ->and($this->owner->fresh()->password)->toBe($accountPassword);

    Storage::disk('public')->assertExists($avatar->path);
    Storage::disk('public')->assertMissing('owner/old-photo.jpg');
    Storage::disk('public')->assertMissing('owner/avatar-new.png');
    Bus::assertDispatched(RebuildResumePdf::class);
});

test('an invalid backup is refused and changes nothing', function (Closure $makeUpload, string $message): void {
    $profile = Profile::factory()->create(['name' => 'Untouched']);
    Storage::disk('public')->put('owner/avatar.webp', 'avatar bytes');
    Media::factory()->create(['key' => MediaKey::Avatar, 'path' => 'owner/avatar.webp']);

    postBackup($makeUpload())->assertSessionHasErrors('file');

    expect(session('errors')->first('file'))->toContain($message)
        ->and($profile->fresh()->name)->toBe('Untouched')
        ->and(Media::query()->sole()->path)->toBe('owner/avatar.webp')
        ->and(Storage::disk('public')->allFiles())->toBe(['owner/avatar.webp']);

    Bus::assertNotDispatched(RebuildResumePdf::class);
})->with([
    'not a zip' => [fn () => UploadedFile::fake()->createWithContent('backup.zip', 'plain text'), 'damaged or incomplete'],
    'an unrelated zip' => [fn () => zipUpload(['notes.txt' => 'hello']), 'no manifest.json'],
    'wrong format' => [fn () => zipUpload(backupEntries(manifest: ['format' => 'something-else'])), "isn't a portfolio backup"],
    'a newer version' => [fn () => zipUpload(backupEntries(manifest: ['version' => 2])), 'backup format 2'],
    'broken json' => [fn () => zipUpload(['manifest.json' => '{not json']), "isn't valid JSON"],
    'changed content' => [function () {
        $entries = backupEntries();
        $entries['content.json'] .= ' ';

        return zipUpload($entries);
    }, 'changed or damaged'],
    'a file the manifest does not list' => [fn () => zipUpload([...backupEntries(), 'files/extra.php' => '<?php']), "don't match its manifest"],
    'a file missing from the zip' => [function () {
        $entries = backupEntries(files: ['files/avatar.png' => pngContents()]);
        unset($entries['files/avatar.png']);

        return zipUpload($entries);
    }, "don't match its manifest"],
    'a stored file the content does not list' => [fn () => zipUpload(backupEntries(files: ['files/avatar.png' => pngContents()])), 'does not list'],
    'an unsafe link' => [fn () => zipUpload(backupEntries(['lists' => [...array_fill_keys(array_column(App\Enums\PortfolioList::cases(), 'value'), []), 'social-links' => [
        ['platform' => 'Evil', 'url' => 'javascript:alert(1)', 'icon' => 'send'],
    ]]])), 'lists.social-links.0.url'],
    'a missing required field' => [fn () => zipUpload(backupEntries(['profile' => ['headline' => 'No name']])), 'profile.name'],
    'an unknown section' => [fn () => zipUpload(backupEntries(['sections' => ['made-up' => ['title' => 'Nope']]])), 'sections'],
    'an invalid setting' => [fn () => zipUpload(backupEntries(['settings' => ['authentication' => ['requireEmailVerification' => true], 'mail' => ['fromAddress' => 'not-an-email']]])), 'valid email address'],
    'an unknown media key' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'shell', 'file' => 'files/shell.png']]], ['files/shell.png' => pngContents()])), 'media.0.key'],
    'a repeated media key' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'avatar', 'file' => 'files/avatar.png'], ['key' => 'avatar', 'file' => 'files/avatar.png']]], ['files/avatar.png' => pngContents()])), 'media.0.key'],
    'a path outside the files folder' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'avatar', 'file' => '../../public/avatar.png']]], ['../../public/avatar.png' => pngContents()])), "isn't an allowed file"],
    'a script disguised as an image' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'avatar', 'file' => 'files/avatar.png']]], ['files/avatar.png' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'])), "isn't really a .png"],
    'a type the key does not allow' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'avatar', 'file' => 'files/avatar.svg']]], ['files/avatar.svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>'])), "isn't an allowed file"],
    'a slash in a file name' => [fn () => zipUpload(backupEntries(['media' => [['key' => 'avatar', 'file' => 'files/avatar.png', 'original_name' => 'a/b.png']]], ['files/avatar.png' => pngContents()])), 'media.0.original_name'],
    'an unknown job status' => [fn () => zipUpload(backupEntries(['job_hunts' => [
        ['title' => 'Hunt', 'started_on' => '2026-01-01', 'applications' => [['company' => 'Acme', 'role' => 'Engineer', 'status' => 'hacked', 'events' => []]]],
    ]])), 'job_hunts.0.applications.0.status'],
    'a file that expands too far' => [fn () => zipUpload(backupEntries(files: ['files/avatar.png' => str_repeat('0', 21 * 1024 * 1024)])), 'too large'],
]);

test('job hunts survive an export and import unchanged', function (): void {
    $hunt = JobHunt::factory()->ended()->create(['title' => 'Last year', 'goal' => 'Senior', 'work_modes' => ['remote'], 'countries' => 'Germany', 'open_to_relocation' => true]);
    $application = JobApplication::factory()->for($hunt, 'hunt')->create(['company' => 'Acme', 'status' => JobApplicationStatus::Rejected, 'chance' => 'low', 'work_mode' => 'hybrid', 'relocation' => 'not_offered']);
    JobApplicationEvent::factory()->for($application, 'application')->create(['body' => 'Recruiter call']);
    JobHunt::factory()->create(['title' => 'Running']);

    $before = downloadBackup();

    JobHunt::query()->delete();

    postBackup(zipUpload($before))->assertSessionHasNoErrors()->assertRedirect();

    $after = downloadBackup();
    unset($before['manifest.json'], $after['manifest.json']);

    expect($after)->toBe($before)
        ->and(json_decode($after['content.json'], true)['job_hunts'][0]['applications'][0]['events'][0]['body'])->toBe('Recruiter call')
        ->and(JobHunt::query()->where('title', 'Last year')->sole())
        ->work_modes->all()->toBe([App\Enums\WorkMode::Remote])
        ->open_to_relocation->toBeTrue()
        ->and(JobApplication::query()->sole()->relocation)->toBe(App\Enums\JobRelocation::NotOffered);
});

test('resumes, their selections and overrides, and sent pdfs survive an export and import', function (): void {
    Storage::fake(ResumeBuild::DISK);
    Bus::fake();
    $first = Experience::factory()->create(['role' => 'First', 'sort_order' => 0]);
    $second = Experience::factory()->create(['role' => 'Second', 'sort_order' => 1]);
    $application = JobApplication::factory()->create(['company' => 'Acme']);
    $tailored = Resume::factory()->create([
        'name' => 'Acme',
        'slug' => 'acme',
        'job_application_id' => $application->id,
        'selection' => ['experience' => [['id' => $second->id, 'included' => true], ['id' => $first->id, 'included' => false]]],
        'overrides' => ['highlights' => [$second->id => ['items' => ['Cut costs by 20%'], 'base_hash' => 'abc']]],
    ]);
    Storage::disk(ResumeBuild::DISK)->put('resume-builds/sent.pdf', '%PDF-1.4 sent');
    $sent = ResumeBuild::factory()->ready()->create(['resume_id' => $tailored->id, 'path' => 'resume-builds/sent.pdf']);
    $application->update(['resume_build_id' => $sent->id]);
    ResumeBuild::factory()->ready()->create(['resume_id' => $tailored->id]);

    $backup = downloadBackup();

    Experience::query()->delete();
    JobHunt::query()->delete();
    Resume::query()->whereKey($tailored->id)->delete();

    postBackup(zipUpload($backup))->assertSessionHasNoErrors()->assertRedirect();

    $restored = Resume::query()->where('slug', 'acme')->sole();
    $restoredApplication = JobApplication::query()->sole();
    $secondId = Experience::query()->where('role', 'Second')->value('id');

    expect($restored->job_application_id)->toBe($restoredApplication->id)
        ->and($restored->selectionFor(App\Enums\ResumeList::Experience)[0])->toBe(['id' => $secondId, 'included' => true])
        ->and($restored->highlightsOverride($secondId))->toBe(['Cut costs by 20%'])
        ->and($restored->builds()->where('status', App\Enums\ResumePdfStatus::Ready)->count())->toBe(1)
        ->and(Storage::disk(ResumeBuild::DISK)->get($restoredApplication->sentBuild->path))->toBe('%PDF-1.4 sent')
        ->and(Resume::public()->slug)->toBe('main');
});

test('a backup made before resumes existed keeps its projects off the resume as they were', function (): void {
    $backup = backupEntries(['lists' => [
        ...array_fill_keys(array_column(App\Enums\PortfolioList::cases(), 'value'), []),
        'projects' => [
            ['name' => 'Kept', 'description' => 'On it.', 'stars' => 0, 'language' => 'PHP', 'tier' => '2', 'technologies' => [], 'show_on_resume' => true],
            ['name' => 'Dropped', 'description' => 'Off it.', 'stars' => 0, 'language' => 'PHP', 'tier' => '2', 'technologies' => [], 'show_on_resume' => false],
        ],
    ]]);

    postBackup(zipUpload($backup))->assertSessionHasNoErrors();

    $included = collect(Resume::public()->selectionFor(App\Enums\ResumeList::Projects))->where('included', true)->pluck('id')->all();

    expect(Project::query()->whereIn('id', $included)->pluck('name')->all())->toBe(['Kept']);
});

test('a backup that lists the resume pdf as an image file still imports', function (): void {
    $backup = backupEntries(['media' => [['key' => 'resume_pdf', 'file' => 'files/resume_pdf.pdf', 'original_name' => 'Resume.pdf']]], ['files/resume_pdf.pdf' => '%PDF-1.4 old']);

    postBackup(zipUpload($backup))->assertSessionHasNoErrors();
});

test('a backup made before job hunts existed leaves the hunts alone', function (): void {
    JobHunt::factory()->create(['title' => 'Kept']);

    postBackup(zipUpload(backupEntries()))->assertSessionHasNoErrors();

    expect(JobHunt::query()->sole()->title)->toBe('Kept');
});

test('a failure while restoring leaves the site as it was', function (): void {
    $profile = Profile::factory()->create(['name' => 'Untouched']);
    $backup = zipUpload(backupEntries(['profile' => ['name' => 'New'], 'media' => [['key' => 'avatar', 'file' => 'files/avatar.png']]], ['files/avatar.png' => pngContents()]));

    Profile::creating(fn () => throw new RuntimeException('Database failure'));

    $this->withoutExceptionHandling();

    expect(fn () => postBackup($backup))->toThrow(RuntimeException::class, 'Database failure');

    expect($profile->fresh()->name)->toBe('Untouched')
        ->and(Media::query()->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles())->toBe([]);

    Bus::assertNotDispatched(RebuildResumePdf::class);
});

test('a backup without a file is refused', function (): void {
    $this->from(route('settings.backup.show'))->post(route('settings.backup.store'))->assertSessionHasErrors('file');
});

test('an export leaves out saved API keys but keeps the notes for the assistant', function (): void {
    resolve(App\Settings\SettingsRepository::class)->save(new AiSettings()->fill(['provider' => AiProvider::OpenRouter, 'openRouterApiKey' => 'sk-or-secret', 'ownerNotes' => 'Senior Laravel roles only.']));

    $entries = downloadBackup();

    expect(json_decode($entries['content.json'], true)['settings']['ai'])->toBe([
        'provider' => 'openrouter',
        'chatModel' => null,
        'fastModel' => null,
        'ownerNotes' => 'Senior Laravel roles only.',
    ])->and(implode('', $entries))->not->toContain('sk-or-secret');
});

test('an import restores the AI choices and keeps the saved API keys', function (): void {
    $repository = resolve(App\Settings\SettingsRepository::class);
    $repository->save(new AiSettings()->fill(['provider' => AiProvider::OpenRouter, 'chatModel' => 'openai/gpt-6']));
    $backup = zipUpload(downloadBackup());
    $repository->save(new AiSettings()->fill(['anthropicApiKey' => 'sk-ant-current']));

    postBackup($backup)->assertSessionHasNoErrors();

    $settings = resolve(AiSettings::class);

    expect($settings->provider)->toBe(AiProvider::OpenRouter)
        ->and($settings->chatModel)->toBe('openai/gpt-6')
        ->and($settings->anthropicApiKey)->toBe('sk-ant-current');
});

test('an import of a backup made before a settings group existed keeps that group as it is', function (): void {
    resolve(App\Settings\SettingsRepository::class)->save(new AiSettings()->fill(['provider' => AiProvider::OpenRouter]));

    postBackup(zipUpload(backupEntries()))->assertSessionHasNoErrors();

    expect(resolve(AiSettings::class)->provider)->toBe(AiProvider::OpenRouter);
});
