<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration\Portfolio;

use App\Actions\Portfolio\UpdatePortfolioCopyAction;
use App\DTOs\PageMeta;
use App\Http\Requests\Administration\Portfolio\PortfolioCopyUpdateRequest;
use App\Models\PageContent;
use App\Models\PortfolioSection;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class PortfolioCopyController
{
    /**
     * Show the section and page copy.
     */
    public function edit(): Response
    {
        return vue('administration/portfolio/Copy', [
            'sections' => PortfolioSection::query()->get()->keyBy('key'),
            'pages' => PageContent::query()->get()->keyBy('key'),
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
