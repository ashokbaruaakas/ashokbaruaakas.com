<?php

declare(strict_types=1);

use App\Ai\Agents\JobHuntAssistant;
use App\Ai\Agents\JobKeywordReader;
use App\Models\AtsCheck;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

const JOB_POST = 'Acme is hiring a Senior Laravel Engineer to build its billing platform with PHP, Laravel, Vue and Kubernetes.';

beforeEach(function (): void {
    config(['ai.providers.anthropic.key' => 'sk-ant-test']);
    Http::preventStrayRequests();
});

function strongResume(): void
{
    Profile::factory()->create([
        'headline' => 'Senior Laravel Engineer',
        'email' => 'ada@example.com',
        'phone' => '+4915112345678',
        'location' => 'Berlin',
        'professional_summary' => 'Senior Laravel Engineer with eight years of experience building PHP and Vue platforms for payments and logistics companies. '
            . 'Cut checkout time by 40 percent for two million monthly buyers and scaled order processing to 5,000 jobs per minute. '
            . 'Focused on clean architecture, automated testing and fast delivery.',
    ]);
    SocialLink::factory()->create(['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/ada']);
    SkillCategory::factory()->create(['name' => 'Backend', 'items' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'Vue', 'Docker', 'C#', 'Node.js']]);
    Experience::factory()->create(['period' => 'Jan 2023 - Present', 'highlights' => [
        'Built a Laravel and Vue checkout that cut payment time by 40% for two million monthly buyers',
        'Scaled Redis queues on Docker to process 5,000 MySQL order jobs per minute without downtime',
        'Led a team of four engineers migrating 30 PHP services and a Node.js gateway to C# microservices',
    ]]);
    Education::factory()->create();
}

test('a resume written for applicant tracking systems passes every check', function (): void {
    strongResume();

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'))->assertRedirect();

    $check = AtsCheck::query()->sole();

    expect($check->score)->toBe(100)
        ->and($check->resume_score)->toBe(100)
        ->and($check->job_match_score)->toBeNull()
        ->and(array_filter(array_column($check->checks, 'tip')))->toBe([]);

    JobKeywordReader::assertNeverPrompted();
});

test('the check scores the chosen resume with its own selection and rewording', function (): void {
    strongResume();
    $resume = App\Models\Resume::factory()->create(['name' => 'Bare', 'overrides' => ['summary' => ['text' => 'Short.', 'base_hash' => 'x']]]);

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'), ['resume_id' => $resume->id])->assertSessionHasNoErrors();

    $check = AtsCheck::query()->sole();

    expect($check->resume_name)->toBe('Bare')
        ->and($check->resume_score)->toBeLessThan(100);
});

test('the check scores the public resume unless another is chosen', function (): void {
    strongResume();

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'));

    expect(AtsCheck::query()->sole()->resume_name)->toBe('Main');
});

test('an archived resume cannot be checked', function (): void {
    strongResume();
    $resume = App\Models\Resume::factory()->create(['archived_at' => now()]);

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'), ['resume_id' => $resume->id])->assertSessionHasErrors('resume_id');
});

test('weak highlights lose points and get a tip on what to fix', function (): void {
    strongResume();
    Experience::query()->update(['highlights' => ['Responsible for the backend', 'I worked on many features', 'Helped the team']]);

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'));

    $checks = collect(AtsCheck::query()->sole()->checks)->keyBy('key');

    expect($checks['action_verbs']['points'])->toBe(0)
        ->and($checks['quantified']['points'])->toBe(0)
        ->and($checks['pronouns']['points'])->toBe(0)
        ->and($checks['quantified']['tip'])->toContain('Add numbers')
        ->and($checks['contact']['tip'])->toBeNull();
});

test('a pasted job post scores how many of its keywords the resume has', function (): void {
    strongResume();
    JobKeywordReader::fake([['title' => 'Senior Laravel Engineer', 'keywords' => ['Laravel', 'laravel', 'Vue', 'Kubernetes', 'C#', 'Node.js']]]);

    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'), ['job_post' => JOB_POST])->assertSessionHasNoErrors();

    $check = AtsCheck::query()->sole();

    expect($check->job_title)->toBe('Senior Laravel Engineer')
        ->and($check->matched_keywords)->toBe(['Laravel', 'Vue', 'C#', 'Node.js'])
        ->and($check->missing_keywords)->toBe(['Kubernetes'])
        ->and($check->job_match_score)->toBe(80)
        ->and($check->score)->toBe(88);
});

test('a job post needs an API key and a readable post', function (): void {
    strongResume();
    $owner = User::factory()->create();

    JobKeywordReader::fake([['title' => null, 'keywords' => []]]);
    $this->actingAs($owner)->post(route('ats-checks.store'), ['job_post' => JOB_POST])
        ->assertSessionHasErrors(['job_post' => 'Anthropic found no keywords in the post. Try again, or pick a stronger fast model under Settings, AI.']);

    config(['ai.providers.anthropic.key' => null]);
    $this->post(route('ats-checks.store'), ['job_post' => JOB_POST])
        ->assertSessionHasErrors(['job_post' => 'Add an API key for Anthropic under Settings, AI to match a job post.']);

    expect(AtsCheck::query()->count())->toBe(0);
});

test('the check waits until the profile exists', function (): void {
    $this->actingAs(User::factory()->create())->post(route('ats-checks.store'))->assertRedirect();

    expect(AtsCheck::query()->count())->toBe(0);
});

test('guests cannot run the check', function (): void {
    $this->post(route('ats-checks.store'))->assertRedirect(route('login'));
});

test('the dashboard defers the latest check', function (): void {
    AtsCheck::factory()->create(['score' => 40]);
    AtsCheck::factory()->create(['score' => 75]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('atsCheck')
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('atsCheck.score', 75)));
});

test('the assistant writes for applicant tracking systems and recruiters', function (): void {
    expect(resolve(JobHuntAssistant::class)->instructions())
        ->toContain('applicant tracking system (ATS)')
        ->toContain('Mirror the exact wording of the target job post');
});
