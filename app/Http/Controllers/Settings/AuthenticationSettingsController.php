<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateAuthenticationSettingsAction;
use App\DTOs\PageMeta;
use App\DTOs\Settings\AuthenticationSettingsDTO;
use App\Http\Requests\Settings\AuthenticationSettingsUpdateRequest;
use App\Settings\AuthenticationSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class AuthenticationSettingsController
{
    /**
     * Show the authentication settings form.
     */
    public function edit(AuthenticationSettings $settings): Response
    {
        return vue('settings/Authentication', [
            'settings' => $settings->toArray(),
            'mailEnabled' => config()->boolean('mail.enabled'),
        ], new PageMeta(
            title: 'Authentication Settings',
        ));
    }

    /**
     * Save the authentication settings.
     */
    public function update(AuthenticationSettingsUpdateRequest $request, UpdateAuthenticationSettingsAction $action): RedirectResponse
    {
        $action->handle(AuthenticationSettingsDTO::from($request));

        toastSuccess('Settings saved.');

        return back();
    }
}
