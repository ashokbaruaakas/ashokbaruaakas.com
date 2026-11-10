<?php

declare(strict_types=1);

namespace App\Models;

use App\Ai\JobChange;
use App\Ai\PortfolioSection;
use App\Enums\ProposalStatus;
use Database\Factories\AssistantProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * A change to the portfolio or to a job, or a new job for a hunt, the assistant suggests. Nothing changes until the owner
 * applies it. A job change applied with a new status keeps the timeline entry it added in `created_item_id`, and a new
 * job keeps the job it added there, with its hunt in `item_id`.
 *
 * @property int $id
 * @property string|null $conversation_id
 * @property string|null $message_id
 * @property string $section
 * @property int|null $item_id
 * @property int|null $created_item_id
 * @property string $summary
 * @property array<string, mixed> $changes
 * @property array<string, mixed>|null $before
 * @property ProposalStatus $status
 * @property Carbon|null $applied_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AssistantProposal extends Model
{
    /** @use HasFactory<AssistantProposalFactory> */
    use HasFactory;

    /**
     * Whether two field values are the same once read the way a form submits them, so a stored 1 equals a proposed "1".
     */
    public static function sameValue(mixed $first, mixed $second): bool
    {
        $normalize = fn (mixed $value): mixed => match (true) {
            is_array($value) => array_map(fn (mixed $item): string => is_scalar($item) ? (string) $item : '', array_values($value)),
            is_bool($value), $value === null => $value,
            is_scalar($value) => (string) $value,
            default => $value,
        };

        return $normalize($first) === $normalize($second);
    }

    public function isForJob(): bool
    {
        return $this->section === JobChange::SECTION;
    }

    public function addsJob(): bool
    {
        return $this->section === JobChange::NEW_JOB_SECTION;
    }

    public function hunt(): ?JobHunt
    {
        return $this->addsJob() ? JobHunt::query()->find($this->item_id) : null;
    }

    public function job(): ?JobApplication
    {
        return $this->isForJob() ? JobApplication::query()->find($this->item_id) : null;
    }

    public function portfolioSection(): ?PortfolioSection
    {
        return PortfolioSection::tryFrom($this->section);
    }

    /**
     * Whether the proposal adds a new item to a list instead of changing one.
     */
    public function addsItem(): bool
    {
        return ! $this->isForJob() && $this->before === null;
    }

    /**
     * Whether the owner changed one of the proposed fields after the assistant read it.
     */
    public function isStale(): bool
    {
        if ($this->addsItem() || $this->status !== ProposalStatus::Pending) {
            return false;
        }

        if ($this->isForJob()) {
            $job = $this->job();

            return ! $job instanceof JobApplication
                || array_any($this->before ?? [], fn (mixed $value, string $field): bool => ! self::sameValue(JobChange::stored($job, $field), $value));
        }

        $item = $this->portfolioSection()?->find($this->item_id);

        if (! $item instanceof Model) {
            return true;
        }

        return array_any($this->before ?? [], fn (mixed $value, string $field): bool => ! self::sameValue($item->getAttribute($field), $value));
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
            'changes' => 'array',
            'before' => 'array',
            'status' => ProposalStatus::class,
            'applied_at' => 'datetime',
        ];
    }
}
