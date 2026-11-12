<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumePdfStatus;
use App\Enums\ResumeTemplate;
use Database\Factories\ResumeFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * A named configuration over the portfolio that renders to a resume PDF.
 *
 * @phpstan-type SelectionEntry array{id: int, included: bool}
 * @phpstan-type TextOverride array{text: string, base_hash: string}
 * @phpstan-type HighlightsOverride array{items: list<string>, base_hash: string}
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ResumeTemplate $template
 * @property ResumeAccent|null $accent
 * @property ResumeFont|null $font
 * @property int|null $page_count
 * @property bool $is_public
 * @property array<string, list<SelectionEntry>>|null $selection
 * @property array{headline?: TextOverride, summary?: TextOverride, highlights?: array<int|string, HighlightsOverride>}|null $overrides
 * @property int|null $job_application_id
 * @property Carbon|null $archived_at
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobApplication|null $jobApplication
 */
final class Resume extends Model
{
    /** @use HasFactory<ResumeFactory> */
    use HasFactory;

    public static function public(): self
    {
        return self::query()->where('is_public', true)->firstOrFail();
    }

    /**
     * Fingerprint the portfolio text an override replaces, so the resume can
     * tell when the portfolio has moved on underneath it.
     *
     * @param  string|list<string>  $text
     */
    public static function baseHash(string|array $text): string
    {
        return hash('sha256', is_array($text) ? implode("\n", $text) : $text);
    }

    /**
     * The PDF visitors download: the public resume's current one, or while a
     * newly public resume has none yet, the PDF of the last resume that was public.
     */
    public static function publicPdf(): ?ResumeBuild
    {
        $public = self::public();

        return $public->currentBuild() ?? self::query()
            ->whereKeyNot($public->id)
            ->whereNotNull('published_at')
            ->whereHas('builds', fn (Builder $query) => $query->where('status', ResumePdfStatus::Ready))
            ->latest('published_at')
            ->first()
            ?->currentBuild();
    }

    public function hasSentBuilds(): bool
    {
        return $this->builds()->whereHas('sentWith')->exists();
    }

    /**
     * Delete the resume with its builds and their PDFs.
     */
    public function deleteWithPdfs(): void
    {
        $paths = $this->builds()->whereNotNull('path')->pluck('path')->all();

        $this->delete();

        Storage::disk(ResumeBuild::DISK)->delete($paths);
    }

    /**
     * The template, colour and font the resume renders with. An unset colour is
     * Emerald and an unset font follows the template.
     *
     * @return array{template: string, accent: string, accentColor: string, font: string}
     */
    public function design(): array
    {
        $accent = $this->accent ?? ResumeAccent::Emerald;

        return [
            'template' => $this->template->value,
            'accent' => $accent->value,
            'accentColor' => $accent->color(),
            'font' => ($this->font ?? $this->template->defaultFont())->value,
        ];
    }

    /**
     * @return list<SelectionEntry>
     */
    public function selectionFor(ResumeList $list): array
    {
        return $this->selection[$list->value] ?? [];
    }

    /**
     * @param  'headline'|'summary'  $field
     */
    public function textOverride(string $field): ?string
    {
        return $this->overrides[$field]['text'] ?? null;
    }

    /**
     * @return list<string>|null
     */
    public function highlightsOverride(?int $experienceId): ?array
    {
        if ($experienceId === null) {
            return null;
        }

        return $this->overrides['highlights'][$experienceId]['items'] ?? null;
    }

    /**
     * @return HasMany<ResumeBuild, $this>
     */
    public function builds(): HasMany
    {
        return $this->hasMany(ResumeBuild::class);
    }

    public function currentBuild(): ?ResumeBuild
    {
        return $this->builds()->where('status', ResumePdfStatus::Ready)->latest('built_at')->latest('id')->first();
    }

    /**
     * @return BelongsTo<JobApplication, $this>
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'template' => ResumeTemplate::class,
            'accent' => ResumeAccent::class,
            'font' => ResumeFont::class,
            'page_count' => 'integer',
            'is_public' => 'boolean',
            'selection' => 'array',
            'overrides' => 'array',
            'archived_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
