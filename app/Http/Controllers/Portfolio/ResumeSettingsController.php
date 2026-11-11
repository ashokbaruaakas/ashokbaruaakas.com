<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\GetPortfolioDataAction;
use App\Actions\Portfolio\ScheduleResumePdfRebuildAction;
use App\DTOs\PageMeta;
use App\Enums\ResumeTemplate;
use App\Http\Requests\Portfolio\ResumeSettingsUpdateRequest;
use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class ResumeSettingsController
{
    /**
     * Show the public resume's template and page limit.
     */
    public function edit(): Response
    {
        $resume = Resume::public();

        return vue('portfolio/Resume', [
            'resume' => [
                'template' => $resume->template->value,
                'pageLimit' => $resume->page_limit,
                'pageCount' => $resume->page_count,
                'isOverPageLimit' => $resume->isOverPageLimit(),
            ],
            'templates' => array_map(fn (ResumeTemplate $template): array => [
                'value' => $template->value,
                'label' => $template->label(),
                'description' => $template->description(),
                'isAtsSafe' => $template->isAtsSafe(),
            ], ResumeTemplate::cases()),
        ], new PageMeta(
            title: 'Resume',
        ));
    }

    /**
     * Render the owner's content with a template, for the template picker.
     */
    public function preview(ResumeTemplate $template, GetPortfolioDataAction $getPortfolioData): Response
    {
        return vue('resume/Preview', [
            'portfolio' => $getPortfolioData->handle(...),
            'template' => $template->value,
        ]);
    }

    /**
     * Save the public resume's template and page limit, rebuilding the PDF
     * right away when the template changes.
     */
    public function update(ResumeSettingsUpdateRequest $request, ScheduleResumePdfRebuildAction $scheduleRebuild): RedirectResponse
    {
        $resume = Resume::public();

        $resume->update([
            'template' => $request->enum('template', ResumeTemplate::class),
            'page_limit' => $request->integer('page_limit'),
        ]);

        if ($resume->wasChanged('template')) {
            $scheduleRebuild->handle(immediately: true);
        }

        toastSuccess('Resume settings saved.');

        return back();
    }
}
