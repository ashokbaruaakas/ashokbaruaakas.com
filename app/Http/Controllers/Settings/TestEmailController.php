<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\SendTestEmailAction;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class TestEmailController
{
    /**
     * Send a test email to the signed-in administrator.
     */
    public function store(SendTestEmailAction $action): RedirectResponse
    {
        $user = authUser();

        if (! config()->boolean('mail.enabled')) {
            toastError('Mail is off. Set MAIL_MAILER in .env to turn it on.');

            return back();
        }

        try {
            $action->handle($user);
        } catch (TransportExceptionInterface $exception) {
            toastError('The test email could not be sent.')->description($exception->getMessage());

            return back();
        }

        toastSuccess("Test email sent to {$user->email}.");

        return back();
    }
}
