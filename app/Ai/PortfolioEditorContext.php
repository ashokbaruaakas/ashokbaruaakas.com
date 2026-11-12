<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Database\Eloquent\Model;

/**
 * The profile or list item the owner is editing while they talk to the assistant, with the unsaved values its form shows.
 * A list item that is not saved yet has no item.
 */
final readonly class PortfolioEditorContext
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public PortfolioSection $section,
        public ?Model $item,
        public array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public static function from(PortfolioSection $section, ?Model $item, array $values): self
    {
        return new self($section, $item, array_intersect_key($values, $section->fields()));
    }

    public function itemId(): ?int
    {
        $id = $this->item?->getKey();

        return is_int($id) ? $id : null;
    }

    public function addsItem(): bool
    {
        return $this->section->isList() && ! $this->item instanceof Model;
    }

    /**
     * Whether a change to this section and item is a change to what the form shows.
     */
    public function isEditing(PortfolioSection $section, ?Model $item): bool
    {
        if ($section->key !== $this->section->key || $this->addsItem()) {
            return false;
        }

        return ! $section->isList() || $this->item?->is($item) === true;
    }

    /**
     * A field's value as the form shows it, or as saved when the form does not hold it.
     */
    public function value(?Model $item, string $field): mixed
    {
        return array_key_exists($field, $this->values) ? $this->values[$field] : $item?->getAttribute($field);
    }
}
