<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\AssistantTurn;
use App\Ai\JobChange;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ProposeJobChange implements Tool
{
    public function __construct(private AssistantTurn $turn) {}

    public function description(): string
    {
        return 'Suggest a change to one job in the job hunt: a new status, a next step and its date, an applied date, notes or any other field. '
            . 'The owner sees it as a card with an Apply button; nothing changes until they apply it, and a status change then goes on the job\'s timeline. '
            . 'Use the stored values: status, chance, work_mode and relocation take the values listed in the schema, dates are YYYY-MM-DD, '
            . 'and an empty value clears a field.';
    }

    public function handle(Request $request): string
    {
        $application = JobApplication::query()->find($request->integer('job_id'));

        if (! $application instanceof JobApplication) {
            return 'There is no job with that id.';
        }

        $fields = JobChange::fields();
        $changes = [];

        foreach ($request->array('changes') as $change) {
            if (! is_array($change)) {
                continue;
            }

            $name = is_string($change['field'] ?? null) ? $change['field'] : '';

            if (! in_array($name, $fields, true)) {
                return "A job has no field named \"{$name}\". Its fields are: " . implode(', ', $fields) . '.';
            }

            $value = is_scalar($change['value'] ?? null) ? mb_trim((string) $change['value']) : '';
            $changes[$name] = $value === '' ? null : $value;
        }

        if ($changes === []) {
            return 'Propose at least one field change.';
        }

        $before = JobChange::before($application, array_keys($changes));

        if (array_all(array_keys($changes), fn (string $name): bool => AssistantProposal::sameValue($changes[$name], $before[$name]))) {
            return 'The job already has these values.';
        }

        $validator = Validator::make($changes, JobChange::rules(array_keys($changes)));

        if ($validator->fails()) {
            return 'The change was not proposed: ' . implode(' ', $validator->errors()->all());
        }

        $proposal = AssistantProposal::query()->create([
            'section' => JobChange::SECTION,
            'item_id' => $application->id,
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
            'job_id' => $schema->integer()->description('The job to change, from the job list or the job this conversation is about.')->required(),
            'summary' => $schema->string()->description('One short line on what the change does, such as "Mark as applied" or "Book the technical interview".')->required(),
            'changes' => $schema->array()
                ->items($schema->object([
                    'field' => $schema->string()->enum(JobChange::fields())->required(),
                    'value' => $schema->string()->description(
                        'The new value. status: wishlist, applied, interviewing, interviewed (waiting to hear back), offer (received), accepted (the offer), declined, rejected, no_response, withdrawn or ignored. '
                        . 'chance: high, medium or low. work_mode: on_site, hybrid or remote. relocation: offered or not_offered. Dates as YYYY-MM-DD.',
                    )->required(),
                ]))
                ->description('The fields to set.')
                ->required(),
        ];
    }
}
