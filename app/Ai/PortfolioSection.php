<?php

declare(strict_types=1);

namespace App\Ai;

use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Enums\PortfolioFieldType;
use App\Enums\PortfolioList;
use App\Models\Profile;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * A part of the portfolio the assistant can read and propose changes to: the profile, or one of the portfolio lists.
 */
final readonly class PortfolioSection
{
    public const string PROFILE = 'profile';

    private function __construct(public string $key, public ?PortfolioList $list) {}

    public static function tryFrom(string $key): ?self
    {
        if ($key === self::PROFILE) {
            return new self($key, null);
        }

        $list = PortfolioList::tryFrom($key);

        return $list instanceof PortfolioList ? new self($key, $list) : null;
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [new self(self::PROFILE, null), ...array_map(fn (PortfolioList $list): self => new self($list->value, $list), PortfolioList::cases())];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $section): string => $section->key, self::all());
    }

    /**
     * Everything the owner can mention in a message: the profile, and every list item.
     *
     * @return list<array{target: string, section: string, label: string}>
     */
    public static function mentionables(): array
    {
        $mentionables = [['target' => 'portfolio:' . self::PROFILE, 'section' => 'Profile', 'label' => 'Profile']];

        foreach (PortfolioList::cases() as $list) {
            $section = new self($list->value, $list);

            foreach ($list->model()::query()->orderBy('sort_order')->get() as $item) {
                $id = $item->getKey();

                if (is_int($id)) {
                    $mentionables[] = [
                        'target' => "portfolio:{$list->value}/{$id}",
                        'section' => $list->title(),
                        'label' => $section->itemLabel($item) ?? "#{$id}",
                    ];
                }
            }
        }

        return $mentionables;
    }

    public function title(): string
    {
        return $this->list?->title() ?? 'Profile';
    }

    public function description(): string
    {
        return $this->list?->description() ?? 'Who the owner is: name, headline, introduction, about, how they work, professional summary, location, contact and open-source interests.';
    }

    public function isList(): bool
    {
        return $this->list instanceof PortfolioList;
    }

    /**
     * The fields the assistant may propose values for. Related records, such as a project's links, are left out.
     *
     * @return array<string, Field>
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->list?->fields() ?? Profile::editableFields() as $field) {
            if ($field->type !== PortfolioFieldType::Links) {
                $fields[$field->name] = $field;
            }
        }

        return $fields;
    }

    /**
     * Find the profile, or an item of the list. Null when it does not exist.
     */
    public function find(?int $itemId): ?Model
    {
        if (! $this->list instanceof PortfolioList) {
            return Profile::query()->first();
        }

        return $itemId === null ? null : $this->list->model()::query()->find($itemId);
    }

    /**
     * Everything in this section, as the assistant reads it. List items carry their id.
     *
     * @return array<mixed>|null
     */
    public function read(): ?array
    {
        if (! $this->list instanceof PortfolioList) {
            return Profile::query()->first()?->only(array_keys($this->fields()));
        }

        $query = $this->list->model()::query()->orderBy('sort_order');

        if ($this->list === PortfolioList::Projects) {
            $query->with('links');
        }

        return $query->get()->map($this->readItem(...))->all();
    }

    /**
     * One list item as the assistant reads it, with its id.
     *
     * @return array<string, mixed>
     */
    public function readItem(Model $item): array
    {
        return [
            'id' => $item->getKey(),
            ...$item->only($this->list?->storedAttributes() ?? array_keys($this->fields())),
            ...$item instanceof Project ? ['links' => $item->links->map->only(['label', 'url'])->all()] : [],
        ];
    }

    /**
     * The name the owner knows an item by, such as a project's name.
     */
    public function itemLabel(?Model $item): ?string
    {
        if (! $this->list instanceof PortfolioList || ! $item instanceof Model) {
            return null;
        }

        $label = $item->getAttribute($this->list->titleField());

        return is_scalar($label) ? (string) $label : null;
    }
}
