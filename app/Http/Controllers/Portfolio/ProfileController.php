<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Assistant\GetAssistantPanelDataAction;
use App\Actions\Portfolio\UpdateProfileAction;
use App\DTOs\PageMeta;
use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Http\Requests\Portfolio\ProfileUpdateRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class ProfileController
{
    /**
     * Show the profile form, with the assistant window beside it.
     */
    public function edit(Request $request, GetAssistantPanelDataAction $getAssistantPanelData): Response
    {
        return vue('portfolio/Profile', [
            'profile' => Profile::query()->first(),
            'fields' => array_map(fn (PortfolioFieldDTO $field): array => $field->toArray(), Profile::editableFields()),
            ...$getAssistantPanelData->handle($request),
        ], new PageMeta(
            title: 'Profile',
        ));
    }

    /**
     * Save the profile.
     */
    public function update(ProfileUpdateRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        $action->handle($attributes);

        toastSuccess('Profile saved.');

        return back();
    }
}
