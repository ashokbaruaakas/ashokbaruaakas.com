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

            return $highlights === null ? $entry : new ExperienceDTO(...[...get_object_vars($entry), 'highlights' => $highlights]);
        }, $portfolio->experience);

        $headline = $resume->textOverride('headline');

        return new PortfolioDTO(...[
            ...get_object_vars($portfolio),
            'tagline' => $headline ?? $portfolio->tagline,
            'headline' => $headline ?? $portfolio->headline,
            'professionalSummary' => $resume->textOverride('summary') ?? $portfolio->professionalSummary,
            'experience' => $this->select($resume, ResumeList::Experience, $experience),
            'projects' => $this->select($resume, ResumeList::Projects, $portfolio->projects),
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
     * @return array<int, TItem>
     */
    private function select(Resume $resume, ResumeList $list, array $items): array
    {
        $remaining = [];

        foreach ($items as $item) {
            $remaining[$item->id] = $item;
        }

        $selected = [];

        foreach ($resume->selectionFor($list) as $entry) {
            if ($entry['included'] && isset($remaining[$entry['id']])) {
                $selected[] = $remaining[$entry['id']];
            }

            unset($remaining[$entry['id']]);
        }

        foreach ($remaining as $item) {
            if ($resume->is_public && $list->includesByDefault($item instanceof ProjectDTO ? $item->tier : null)) {
                $selected[] = $item;
            }
        }

        return $selected;
    }
}
