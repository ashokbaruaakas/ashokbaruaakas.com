<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateMailSettingsAction;
use App\DTOs\PageMeta;
use App\DTOs\Settings\MailSettingsDTO;
use App\Http\Requests\Settings\MailSettingsUpdateRequest;
use App\Settings\MailSettings;
use App\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class MailSettingsController
{
    /**
     * Show whether mail is on and the mail settings form, with the `.env` values that apply while a field is empty.
     */
    public function edit(MailSettings $settings, SettingsRepository $repository): Response
    {
        return vue('settings/Mail', [
            'settings' => $settings->toArray(),
            'defaults' => [
                'fromName' => $repository->originalConfig('mail.from.name'),
                'fromAddress' => $repository->originalConfig('mail.from.address'),
            ],
            'mailer' => config()->string('mail.default'),
            'enabled' => config()->boolean('mail.enabled'),
        ], new PageMeta(
            title: 'Mail Settings',
        ));
    }

    /**
     * Save the mail settings.
     */
    public function update(MailSettingsUpdateRequest $request, UpdateMailSettingsAction $action): RedirectResponse
    {
        $action->handle(MailSettingsDTO::from($request));

        toastSuccess('Settings saved.');

        return back();
    }
}
