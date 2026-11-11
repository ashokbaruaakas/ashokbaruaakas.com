<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Portfolio\GetResumePdfStatusAction;
use App\Enums\MediaKey;
use App\Enums\ResumePdfStatus;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use Override;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        $user = $request->user();
        $profile = Profile::query()->first(['name', 'headline']);
        $media = Media::query()->get()
            ->mapWithKeys(fn (Media $media): array => [$media->key->value => $media->url()])
            ->all();

        return [
            ...parent::share($request),
            'name' => $profile->name ?? config('app.name'),
            'headline' => $profile?->headline,
            'auth' => [
                'user' => $user?->toResource(),
                'can' => $this->resolveAbilities($user),
            ],
            'seo' => [
                'canonicalUrl' => $this->canonicalUrl($request),
                'homeUrl' => $this->absoluteUrl('/'),
                'imageUrl' => $media[MediaKey::ShareImage->value] ?? null,
                'photoUrl' => $media[MediaKey::Photo->value] ?? null,
            ],
            'media' => $media,
            'hasResumePdf' => fn (): bool => Resume::query()->where('is_public', true)->whereHas('builds', fn (Builder $query) => $query->where('status', ResumePdfStatus::Ready))->exists(),
            'resumePdf' => fn (): ?array => $user instanceof User ? resolve(GetResumePdfStatusAction::class)->handle() : null,
        ];
    }

    /**
     * Build the canonical URL for the incoming request.
     *
     * The URL generator is forced to HTTPS, so the canonical URL stays correct
     * even when TLS is terminated at the edge proxy and the application only
     * ever sees plain HTTP.
     */
    private function canonicalUrl(Request $request): string
    {
        return $this->absoluteUrl($request->getPathInfo());
    }

    /**
     * Generate an absolute URL for the given request path, keeping the
     * trailing slash on the root URL so it matches the sitemap.
     */
    private function absoluteUrl(string $path): string
    {
        return $path === '/' ? url('/') . '/' : url($path);
    }

    /**
     * Resolve the UI-relevant abilities for the given user.
     *
     * Add ability keys here as the application grows. Each key maps directly
     * to a `can()` call in Vue components (e.g. `can('updateProfile')`).
     *
     * @return array<string, bool>
     */
    private function resolveAbilities(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'updateProfile' => false,
                'deleteAccount' => false,
            ];
        }

        $gate = Gate::forUser($user);

        return [
            'updateProfile' => $gate->allows('updateProfile'),
            'deleteAccount' => $gate->allows('deleteAccount'),
        ];
    }
}
