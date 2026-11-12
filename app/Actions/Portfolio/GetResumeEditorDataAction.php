<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use App\Models\ResumeBuild;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Model;

final readonly class GetResumeEditorDataAction
{
    public function __construct(private GetResumePdfStatusAction $getResumePdfStatus) {}

    /**
     * Everything the resume editor shows: the settings and design choices, every
     * selectable item in the resume's order, and the overrides beside the text
     * they replace.
     *
     * @return array<string, mixed>
     */
    public function handle(Resume $resume): array
    {
        $profile = Profile::query()->first();
        $experiences = Experience::query()->orderBy('sort_order')->orderBy('id')->get();

        return [
            'resume' => [
                'id' => $resume->id,
                'name' => $resume->name,
                'slug' => $resume->slug,
                'template' => $resume->template->value,
                'accent' => $resume->accent?->value,
                'font' => $resume->font?->value,
                'pageCount' => $resume->page_count,
                'pdfUrl' => ($build = $resume->currentBuild()) instanceof ResumeBuild ? route('resumes.builds.download', $build) : null,
                'isPublic' => $resume->is_public,
                'isArchived' => $resume->archived_at !== null,
                'hasSentPdfs' => $resume->hasSentBuilds(),
                'jobApplicationId' => $resume->job_application_id,
            ],
            'templates' => array_map(fn (ResumeTemplate $template): array => [
                'value' => $template->value,
                'label' => $template->label(),
                'description' => $template->description(),
                'isAtsSafe' => $template->isAtsSafe(),
                'defaultAccent' => $template->defaultAccent()->value,
                'defaultFont' => $template->defaultFont()->value,
            ], ResumeTemplate::cases()),
            'accents' => array_map(fn (ResumeAccent $accent): array => ['value' => $accent->value, 'label' => $accent->label(), 'color' => $accent->color()], ResumeAccent::cases()),
            'fonts' => array_map(fn (ResumeFont $font): array => ['value' => $font->value, 'label' => $font->label()], ResumeFont::cases()),
            'selection' => array_combine(
                ResumeList::values(),
                array_map(fn (ResumeList $list): array => $this->selectionItems($resume, $list), ResumeList::cases()),
            ),
            'overrides' => [
                'headline' => $this->textOverride($resume, 'headline', $profile->tagline ?? ''),
                'summary' => $this->textOverride($resume, 'summary', $profile->professional_summary ?? ''),
                'highlights' => $experiences->map(fn (Experience $experience): array => [
                    'experienceId' => $experience->id,
                    'label' => "{$experience->role} — {$experience->company}",
                    'base' => $experience->highlights,
                    'override' => $resume->highlightsOverride($experience->id),
                    'isStale' => $this->isStale($resume->overrides['highlights'][$experience->id]['base_hash'] ?? null, $experience->highlights),
                ])->all(),
            ],
            'status' => $this->getResumePdfStatus->handle($resume),
            'jobApplications' => JobApplication::query()->latest('id')->get(['id', 'company', 'role'])
                ->map(fn (JobApplication $application): array => ['id' => $application->id, 'label' => "{$application->company} — {$application->role}"])->all(),
        ];
    }

    /**
     * @return list<array{id: int, label: string, included: bool}>
     */
    private function selectionItems(Resume $resume, ResumeList $list): array
    {
        $items = $list->items()->get()->keyBy(fn (Model $item): int => (int) $item->getKey());
        $ordered = [];

        foreach ($resume->selectionFor($list) as $entry) {
            if ($items->has($entry['id'])) {
                $ordered[] = ['id' => $entry['id'], 'label' => $this->label($items->get($entry['id'])), 'included' => $entry['included']];
                $items->forget($entry['id']);
            }
        }

        foreach ($items as $id => $item) {
            $ordered[] = ['id' => $id, 'label' => $this->label($item), 'included' => $resume->is_public && $list->includesByDefault($item instanceof Project ? $item->tier : null)];
        }

        return $ordered;
    }

    private function label(Model $item): string
    {
        return match (true) {
            $item instanceof Experience => "{$item->role} — {$item->company}",
            $item instanceof Project => $item->tier === 2 ? $item->name : "{$item->name} (featured project)",
            $item instanceof Education => "{$item->degree} — {$item->school}",
            $item instanceof SkillCategory => $item->name,
            default => (string) $item->getKey(),
        };
    }

    /**
     * @return array{base: string, override: string|null, isStale: bool}
     */
    private function textOverride(Resume $resume, string $field, string $base): array
    {
        return [
            'base' => $base,
            'override' => $resume->textOverride($field),
            'isStale' => $this->isStale($resume->overrides[$field]['base_hash'] ?? null, $base),
        ];
    }

    /**
     * @param  string|list<string>  $base
     */
    private function isStale(?string $baseHash, string|array $base): bool
    {
        return $baseHash !== null && ! hash_equals($baseHash, Resume::baseHash($base));
    }
}
