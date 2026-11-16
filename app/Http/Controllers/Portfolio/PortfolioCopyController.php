<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Assistant\GetAssistantPanelDataAction;
use App\Actions\Portfolio\UpdatePortfolioCopyAction;
use App\Ai\PortfolioCopy;
use App\DTOs\PageMeta;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Http\Requests\Portfolio\PortfolioCopyUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class PortfolioCopyController
{
    /**
     * Show the section and page copy, with the assistant window beside it.
     */
    public function edit(Request $request, GetAssistantPanelDataAction $getAssistantPanelData): Response
    {
        return vue('portfolio/Copy', [
            'copy' => PortfolioCopy::values(),
            'fields' => array_map(fn (PortfolioFieldDTO $field): array => $field->toArray(), PortfolioCopy::fields()),
            ...$getAssistantPanelData->handle($request),
        ], new PageMeta(
            title: 'Headings and SEO',
        ));
    }

    /**
     * Save the section and page copy.
     */
    public function update(PortfolioCopyUpdateRequest $request, UpdatePortfolioCopyAction $action): RedirectResponse
    {
        /** @var array<string, array<string, mixed>> $sections */
        $sections = $request->validated('sections');

        /** @var array<string, array<string, mixed>> $pages */
        $pages = $request->validated('pages');

        $action->handle($sections, $pages);

        toastSuccess('Saved.');

        return back();
    }
}
