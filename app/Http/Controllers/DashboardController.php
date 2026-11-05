<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Actions\Portfolio\GetPortfolioContentStatusAction;
use App\DTOs\PageMeta;
use Inertia\Response;

final class DashboardController
{
    /**
     * Show which public pages are live, which portfolio sections hold content and the account's security.
     */
    public function show(GetPortfolioAvailabilityAction $availability, GetPortfolioContentStatusAction $contentStatus): Response
    {
        $user = authUser();

        return vue('Dashboard', [
            'availability' => deferProp(fn (): array => $availability->handle()->toArray()),
            'contentStatus' => deferProp(fn (): array => $contentStatus->handle()),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'passkeyCount' => $user->passkeys()->count(),
        ], new PageMeta(
            title: 'Dashboard',
        ));
    }
}
