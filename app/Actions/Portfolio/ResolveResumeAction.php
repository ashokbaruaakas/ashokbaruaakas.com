<?php

declare(strict_types=1);

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\Enums\ResumeList;
use App\Models\Resume;

final readonly class ResolveResumeAction
{
    public function __construct(private GetPortfolioDataAction $getPortfolioData) {}

    /**
     * Apply a resume's selection and overrides to the portfolio.
     */
    public function handle(Resume $resume): PortfolioDTO
    {
        $portfolio = $this->getPortfolioData->handle();

        $experience = array_map(function (ExperienceDTO $entry) use ($resume): ExperienceDTO {
            $highlights = $resume->highlightsOverride($entry->id);

            return $highlights === null ? $entry : $entry->with(['highlights' => $highlights]);
        }, $portfolio->experience);

        $headline = $resume->textOverride('headline');

        return PortfolioDTO::fromArray([
            ...$this->properties($portfolio),
            'tagline' => $headline ?? $portfolio->tagline,
            'headline' => $headline ?? $portfolio->headline,
            'professionalSummary' => $resume->textOverride('summary') ?? $portfolio->professionalSummary,
            'experience' => $this->select($resume, ResumeList::Experience, $experience),
            'projects' => $this->select($resume, ResumeList::Projects, $portfolio->projects, fn (ProjectDTO $project): int => $project->tier),
            'education' => $this->select($resume, ResumeList::Education, $portfolio->education),
            'skills' => $this->select($resume, ResumeList::Skills, $portfolio->skills),
        ]);
    }

    /**
     * Keep the selected items in the resume's order. Items the selection has not
     * seen yet join the public resume and stay out of the others.
     *
     * @template TItem of object{id: int|null}
     *
     * @param  array<int, TItem>  $items
     * @param  (callable(TItem): int)|null  $tierOf
     * @return array<int, TItem>
     */
    private function select(Resume $resume, ResumeList $list, array $items, ?callable $tierOf = null): array
    {
        $remaining = [];

        foreach ($items as $item) {
            if ($item->id !== null) {
                $remaining[$item->id] = $item;
            }
        }

        $selected = [];

        foreach ($resume->selectionFor($list) as $entry) {
            if ($entry['included'] && isset($remaining[$entry['id']])) {
                $selected[] = $remaining[$entry['id']];
            }

            unset($remaining[$entry['id']]);
        }

        foreach ($remaining as $item) {
            if ($resume->is_public && $list->includesByDefault($tierOf === null ? null : $tierOf($item))) {
                $selected[] = $item;
            }
        }

        return $selected;
    }

    /**
     * A DTO's properties as they are, keeping nested DTOs as objects.
     *
     * @return array<string, mixed>
     */
    private function properties(object $dto): array
    {
        $properties = [];

        foreach (get_object_vars($dto) as $name => $value) {
            $properties[(string) $name] = $value;
        }

        return $properties;
    }
}
