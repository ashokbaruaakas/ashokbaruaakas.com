<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\AssistantTurn;
use App\Ai\PortfolioEditorContext;
use App\Ai\PortfolioSection;
use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Enums\PortfolioFieldType;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ProposePortfolioChange implements Tool
{
    public function __construct(private AssistantTurn $turn, private ?PortfolioEditorContext $editor = null) {}

    public function description(): string
    {
        return 'Suggest a change to the owner\'s portfolio: new values for fields of the profile or of one list item, or a new list item. '
            . 'The owner sees it as a card with an Apply button; nothing changes until they apply it. '
            . 'Read the section first so you know the current values and field names. '
            . 'Write every value to pass an ATS and read well to a recruiter, following your writing rules.';
    }

    public function handle(Request $request): string
    {
        $section = PortfolioSection::tryFrom($request->string('section')->toString());

        if (! $section instanceof PortfolioSection) {
            return 'Unknown section. Use one of: ' . implode(', ', PortfolioSection::keys()) . '.';
        }

        $itemId = $section->isList() && $request->filled('item_id') ? $request->integer('item_id') : null;
        $item = $section->find($itemId);

        if ($itemId !== null && ! $item instanceof Model) {
            return "There is no item with id {$itemId} in {$section->title()}.";
        }

        $fields = $section->fields();
        $changes = [];

        foreach ($request->array('changes') as $change) {
            if (! is_array($change)) {
                continue;
            }

            $name = is_string($change['field'] ?? null) ? $change['field'] : '';

            if (! array_key_exists($name, $fields)) {
                return "{$section->title()} has no field named \"{$name}\". Its fields are: " . implode(', ', array_keys($fields)) . '.';
            }

            $changes[$name] = $this->cast($fields[$name], $change['value'] ?? null);
        }

        if ($changes === []) {
            return 'Propose at least one field change.';
        }

        $addsItem = $section->isList() && $itemId === null;
        $editor = $this->editor?->isEditing($section, $item) === true ? $this->editor : null;
        $before = $addsItem ? null : array_map(
            fn (string $name): mixed => $editor instanceof PortfolioEditorContext ? $editor->value($item, $name) : $item?->getAttribute($name),
            array_combine(array_keys($changes), array_keys($changes)),
        );

        if ($before !== null && array_all(array_keys($changes), fn (string $name): bool => AssistantProposal::sameValue($changes[$name], $before[$name]))) {
            return 'Those fields already have these values.';
        }

        $rules = array_merge(...array_map(
            fn (Field $field): array => $field->rules(),
            $addsItem ? array_values($fields) : array_values(array_intersect_key($fields, $changes)),
        ));
        $validator = Validator::make($changes, $rules);

        if ($validator->fails()) {
            return 'The change was not proposed: ' . implode(' ', $validator->errors()->all());
        }

        $proposal = AssistantProposal::query()->create([
            'section' => $section->key,
            'item_id' => $itemId,
            'summary' => $request->string('summary')->limit(250)->toString(),
            'changes' => $changes,
            'before' => $before,
            'status' => ProposalStatus::Pending,
        ]);

        $this->turn->created($proposal);

        return 'The owner now sees this change as a card under your reply and can apply or dismiss it. Do not repeat the new values in your reply.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()->enum(PortfolioSection::keys())->required(),
            'item_id' => $schema->integer()->description('The list item to change. Leave out to add a new item, and for the profile.'),
            'summary' => $schema->string()->description('One short line on what the change does, such as "Tighten the Acme description".')->required(),
            'changes' => $schema->array()
                ->items($schema->object([
                    'field' => $schema->string()->required(),
                    'value' => $schema->string()->description('The new value. Put each item of a one-per-line field on its own line; use "true" or "false" for yes/no fields.')->required(),
                ]))
                ->description('The fields to set. A new item needs every required field.')
                ->required(),
        ];
    }

    private function cast(Field $field, mixed $value): mixed
    {
        $text = is_scalar($value) ? mb_trim((string) $value) : '';

        return match ($field->type) {
            PortfolioFieldType::Lines => array_values(array_filter(array_map(mb_trim(...), preg_split('/\R/', $text) ?: []), fn (string $line): bool => $line !== '')),
            PortfolioFieldType::Boolean => filter_var($text, FILTER_VALIDATE_BOOLEAN),
            PortfolioFieldType::Number => is_numeric($text) ? (int) $text : $text,
            default => $text === '' ? null : $text,
        };
    }
}
