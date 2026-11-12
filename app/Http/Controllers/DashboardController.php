<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\JobHunt\GetJobHuntSummaryAction;
use App\Actions\Portfolio\GetPortfolioAvailabilityAction;
use App\Actions\Portfolio\GetPortfolioContentStatusAction;
use App\Ai\Conversations;
use App\DTOs\PageMeta;
use App\Models\AssistantConversation;
use App\Models\AtsCheck;
use App\Models\Resume;
use App\Settings\AiSettings;
use Inertia\Response;

final class DashboardController
{
    private const int RECENT_CONVERSATIONS = 3;

    /**
     * Show which public pages are live, which portfolio sections hold content, how the running job hunt
     * is going, the latest ATS score of the resume, the assistant's recent conversations, and the state of the account and setup.
     */
    public function show(
        GetPortfolioAvailabilityAction $availability,
        GetPortfolioContentStatusAction $contentStatus,
        GetJobHuntSummaryAction $jobHunt,
        AiSettings $aiSettings,
    ): Response {
        $user = authUser();

        return vue('Dashboard', [
            'availability' => deferProp(fn (): array => $availability->handle()->toArray()),
            'contentStatus' => deferProp(fn (): array => $contentStatus->handle()),
            'jobHunt' => deferProp(fn (): ?array => $jobHunt->handle()),
            'recentConversations' => deferProp(fn (): array => Conversations::of($user)
                ->latest('updated_at')
                ->limit(self::RECENT_CONVERSATIONS)
                ->get(['id', 'title', 'updated_at'])
                ->map(fn (AssistantConversation $conversation): array => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'updated_at' => $conversation->updated_at?->toIso8601String(),
                ])
                ->all()),
            'atsCheck' => deferProp(fn (): ?array => AtsCheck::query()->latest('id')->first()?->summary()),
            'resumes' => Resume::query()->active()->orderByDesc('is_public')->orderBy('name')->get(['id', 'name', 'is_public'])
                ->map(fn (Resume $resume): array => ['id' => $resume->id, 'name' => $resume->name, 'isPublic' => $resume->is_public])->all(),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'passkeyCount' => $user->passkeys()->count(),
            'ai' => [
                'isReady' => $aiSettings->hasApiKey(),
                'provider' => $aiSettings->provider->label(),
            ],
            'mailEnabled' => config()->boolean('mail.enabled'),
        ], new PageMeta(
            title: 'Dashboard',
        ));
    }
}
