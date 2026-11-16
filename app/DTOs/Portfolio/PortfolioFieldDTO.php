<?php

declare(strict_types=1);

namespace App\DTOs\Portfolio;

use App\Enums\PortfolioFieldType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Validation\Rule;
use TheSoftPulze\LaravibeStandards\DTOs\Concerns\AsDTO;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PortfolioFieldDTO implements Arrayable, Jsonable
{
    use AsDTO;

    /**
     * @param  array<int, array{value: string, label: string}>  $options
     * @param  bool  $assisted  Whether the editor offers to ask the assistant about the field. Off for plain facts such as a name.
     */
    public function __construct(
        public string $name,
        public string $label,
        public PortfolioFieldType $type = PortfolioFieldType::Text,
        public bool $required = false,
        public ?string $help = null,
        public ?string $group = null,
        public array $options = [],
        public bool $assisted = true,
        public ?int $maxLength = null,
    ) {}

    /**
     * Get the validation rules for this field, keyed by input name.
     *
     * Links are limited to http and https because they are rendered as hrefs
     * on public pages, where a javascript: URL would run in a visitor's browser.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $presence = $this->required ? 'required' : 'nullable';

        return match ($this->type) {
            PortfolioFieldType::Text => [$this->name => [$presence, 'string', 'max:' . ($this->maxLength ?? 255)]],
            PortfolioFieldType::Textarea => [$this->name => [$presence, 'string', 'max:' . ($this->maxLength ?? 10000)]],
            PortfolioFieldType::Url => [$this->name => [$presence, 'string', 'max:255', 'url:http,https']],
            PortfolioFieldType::Email => [$this->name => [$presence, 'string', 'max:255', 'email']],
            PortfolioFieldType::Number => [$this->name => [$presence, 'integer', 'min:0']],
            PortfolioFieldType::Boolean => [$this->name => ['boolean']],
            PortfolioFieldType::Select => [$this->name => [$presence, 'string', Rule::in(array_column($this->options, 'value'))]],
            PortfolioFieldType::Lines => [
                $this->name => [$this->required ? 'present' : 'nullable', 'array', 'max:100'],
                "{$this->name}.*" => ['required', 'string', 'max:1000'],
            ],
            PortfolioFieldType::Links => [
                $this->name => ['nullable', 'array', 'max:20'],
                "{$this->name}.*.label" => ['required', 'string', 'max:255'],
                "{$this->name}.*.url" => ['required', 'string', 'max:255', 'url:http,https'],
                "{$this->name}.*.is_primary" => ['boolean'],
            ],
        };
    }

    /**
     * Read a value as typed, such as a line per item for a one-per-line field, the way the field stores it.
     */
    public function cast(mixed $value): mixed
    {
        $text = is_scalar($value) ? mb_trim((string) $value) : '';

        return match ($this->type) {
            PortfolioFieldType::Lines => array_values(array_filter(array_map(mb_trim(...), preg_split('/\R/', $text) ?: []), fn (string $line): bool => $line !== '')),
            PortfolioFieldType::Boolean => filter_var($text, FILTER_VALIDATE_BOOLEAN),
            PortfolioFieldType::Number => is_numeric($text) ? (int) $text : $text,
            default => $text === '' ? null : $text,
        };
    }

    /**
     * How the owner edits a suggested value on the assistant's card, or null when the card does not offer it.
     */
    public function suggestionInput(): ?string
    {
        return match ($this->type) {
            PortfolioFieldType::Text, PortfolioFieldType::Url, PortfolioFieldType::Email => 'text',
            PortfolioFieldType::Textarea => 'textarea',
            PortfolioFieldType::Lines => 'lines',
            default => null,
        };
    }
}
