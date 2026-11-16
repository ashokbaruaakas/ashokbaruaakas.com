<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use App\Ai\JobChange;
use App\DTOs\Portfolio\PortfolioFieldDTO as Field;
use App\Models\AssistantProposal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class ReviseAssistantProposalAction
{
    /**
     * Replace proposed values with the owner's edits of them, checked by the rules the assistant's values passed.
     * Only fields the proposal changes and its card lets the owner edit are taken. Errors are keyed `changes.{field}`.
     *
     * @param  array<string, mixed>  $edits
     *
     * @throws ValidationException
     */
    public function handle(AssistantProposal $proposal, array $edits): void
    {
        $revised = [];
        $rules = [];

        if ($proposal->isForJob() || $proposal->addsJob()) {
            foreach (array_intersect_key($edits, $proposal->changes) as $name => $value) {
                if (JobChange::suggestionInput($name) !== null) {
                    $text = is_scalar($value) ? mb_trim((string) $value) : '';
                    $revised[$name] = $text === '' ? null : $text;
                }
            }

            $rules = JobChange::rules(array_keys($revised));
        } elseif (! $proposal->isForResume()) {
            $fields = $proposal->portfolioSection()?->fields() ?? [];

            foreach (array_intersect_key($edits, $proposal->changes, $fields) as $name => $value) {
                if ($fields[$name]->suggestionInput() !== null) {
                    $revised[$name] = $fields[$name]->cast($value);
                }
            }

            $rules = array_merge([], ...array_map(fn (Field $field): array => $field->rules(), array_values(array_intersect_key($fields, $revised))));
        }

        if ($revised === []) {
            return;
        }

        $validator = Validator::make($revised, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())->mapWithKeys(fn (array $messages, string $key): array => ["changes.{$key}" => $messages])->all(),
            );
        }

        $proposal->changes = [...$proposal->changes, ...$revised];
        $proposal->save();
    }
}
