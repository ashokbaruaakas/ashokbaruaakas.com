<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateGeneralSettingsAction;
use App\DTOs\PageMeta;
use App\DTOs\Settings\GeneralSettingsDTO;
use App\Http\Requests\Settings\GeneralSettingsUpdateRequest;
use App\Settings\GeneralSettings;
use App\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class GeneralSettingsController
{
    /**
     * Show the general settings form.
     */
    public function edit(GeneralSettings $settings, SettingsRepository $repository): Response
    {
        return vue('settings/General', [
            'settings' => $settings->toArray(),
            'defaultSiteName' => $repository->originalConfig('app.name'),
        ], new PageMeta(
            title: 'General settings',
        ));
    }

    /**
     * Save the general settings.
     */
    public function update(GeneralSettingsUpdateRequest $request, UpdateGeneralSettingsAction $action): RedirectResponse
    {
        $action->handle(GeneralSettingsDTO::from($request));

        toastSuccess('Settings saved.');

        return back();
    }
}
