<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\Enums\ResumeList;
use App\Enums\ResumePdfStatus;
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
     * Everything the resume editor shows: the settings, every selectable item in
     * the resume's order, the overrides beside the text they replace, and the
     * build history.
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
                'pageLimit' => $resume->page_limit,
                'pageCount' => $resume->page_count,
                'isOverPageLimit' => $resume->isOverPageLimit(),
                'isPublic' => $resume->is_public,
                'isArchived' => $resume->archived_at !== null,
                'jobApplicationId' => $resume->job_application_id,
            ],
            'templates' => array_map(fn (ResumeTemplate $template): array => [
                'value' => $template->value,
                'label' => $template->label(),
                'description' => $template->description(),
                'isAtsSafe' => $template->isAtsSafe(),
            ], ResumeTemplate::cases()),
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
            'builds' => $resume->builds()->where('status', ResumePdfStatus::Ready)->with('sentWith')->latest('built_at')->latest('id')->get()
                ->map(fn (ResumeBuild $build): array => [
                    'id' => $build->id,
                    'builtAt' => $build->built_at?->toIso8601String(),
                    'template' => $build->template?->label(),
                    'pageCount' => $build->page_count,
                    'sentTo' => $build->sentWith->map(fn (JobApplication $application): string => "{$application->company} — {$application->role}")->all(),
                ])->all(),
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
        $items = $list->portfolioList()->model()::query()->orderBy('sort_order')->orderBy('id')->get()->keyBy(fn (Model $item): int => (int) $item->getKey());
        $ordered = [];

        foreach ($resume->selectionFor($list) as $entry) {
            if ($items->has($entry['id'])) {
                $ordered[] = ['id' => $entry['id'], 'label' => $this->label($items->get($entry['id'])), 'included' => $entry['included']];
                $items->forget($entry['id']);
            }
        }

        foreach ($items as $id => $item) {
            $ordered[] = ['id' => $id, 'label' => $this->label($item), 'included' => $resume->is_public && (! $item instanceof Project || $item->tier === 2)];
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
