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
     */
    public function __construct(
        public string $name,
        public string $label,
        public PortfolioFieldType $type = PortfolioFieldType::Text,
        public bool $required = false,
        public ?string $help = null,
        public ?string $group = null,
        public array $options = [],
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
            PortfolioFieldType::Text => [$this->name => [$presence, 'string', 'max:255']],
            PortfolioFieldType::Textarea => [$this->name => [$presence, 'string', 'max:10000']],
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
}
