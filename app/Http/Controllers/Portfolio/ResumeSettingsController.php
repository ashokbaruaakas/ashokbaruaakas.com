<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

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
     * Save the public resume's template and page limit.
     */
    public function update(ResumeSettingsUpdateRequest $request): RedirectResponse
    {
        Resume::public()->update([
            'template' => $request->enum('template', ResumeTemplate::class),
            'page_limit' => $request->integer('page_limit'),
        ]);

        toastSuccess('Resume settings saved.');

        return back();
    }
}
