<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\AssistantTurn;
use App\Ai\JobChange;
use App\Enums\JobApplicationStatus;
use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use App\Models\JobApplication;
use App\Models\JobHunt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ProposeNewJob implements Tool
{
    public function __construct(private AssistantTurn $turn) {}

    public function description(): string
    {
        return 'Suggest adding a new job to a job hunt, such as one from a job post the owner shared. '
            . 'The owner sees it as a card with an Add button; nothing is added until they click it. '
            . 'Fill in every field the post states, and leave out what it does not. company and role are required. '
            . 'Use the stored values: status, chance, work_mode and relocation take the values listed in the schema, dates are YYYY-MM-DD.';
    }

    public function handle(Request $request): string
    {
        $hunt = $request->filled('hunt_id')
            ? JobHunt::query()->find($request->integer('hunt_id'))
            : JobHunt::query()->active()->latest('started_on')->first();

        if (! $hunt instanceof JobHunt) {
            return $request->filled('hunt_id')
                ? 'There is no hunt with that id.'
                : 'The owner has no running job hunt. Ask them to start one in the job hunt tracker first.';
        }

        $fields = JobChange::fields();
        $values = ['status' => JobApplicationStatus::Wishlist->value];

        foreach ($request->array('fields') as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = is_string($field['field'] ?? null) ? $field['field'] : '';

            if (! in_array($name, $fields, true)) {
                return "A job has no field named \"{$name}\". Its fields are: " . implode(', ', $fields) . '.';
            }

            $value = is_scalar($field['value'] ?? null) ? mb_trim((string) $field['value']) : '';

            if ($value !== '') {
                $values[$name] = $value;
            }
        }

        $validator = Validator::make($values, JobChange::rules([...array_keys($values), 'company', 'role']));

        if ($validator->fails()) {
            return 'The job was not proposed: ' . implode(' ', $validator->errors()->all());
        }

        $existing = $this->existing($hunt, $values);

        if ($existing instanceof JobApplication) {
            return "This hunt already has this job (id {$existing->id}, {$existing->company} — {$existing->role}). Propose changes to it with ProposeJobChange instead.";
        }

        $proposal = AssistantProposal::query()->create([
            'section' => JobChange::NEW_JOB_SECTION,
            'item_id' => $hunt->id,
            'summary' => $request->string('summary')->limit(250)->toString(),
            'changes' => $values,
            'before' => null,
            'status' => ProposalStatus::Pending,
        ]);

        $this->turn->created($proposal);

        return "The owner now sees this job as a card under your reply and can add it to the hunt \"{$hunt->title}\" with one click. Do not repeat its details in your reply.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'hunt_id' => $schema->integer()->description('The hunt to add the job to. Leave it out to use the running hunt.'),
            'summary' => $schema->string()->description('One short line, such as "Add Acme — Backend Engineer to the hunt".')->required(),
            'fields' => $schema->array()
                ->items($schema->object([
                    'field' => $schema->string()->enum(JobChange::fields())->required(),
                    'value' => $schema->string()->description(
                        'The value. status (wishlist unless the owner says they applied): wishlist, applied, interviewing, offer, accepted, declined, rejected, '
                        . 'no_response, withdrawn or ignored. chance: high, medium or low. work_mode: on_site, hybrid or remote. relocation: offered or not_offered. '
                        . 'Dates as YYYY-MM-DD. location is the city only. notes can hold a short summary of the post.',
                    )->required(),
                ]))
                ->description('The job\'s fields. Include company, role and the post\'s link.')
                ->required(),
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    private function existing(JobHunt $hunt, array $values): ?JobApplication
    {
        return $hunt->applications()
            ->where(fn ($query) => $query
                ->when(isset($values['link']), fn ($query) => $query->orWhere('link', $values['link']))
                ->orWhere(fn ($query) => $query
                    ->whereRaw('lower(company) = ?', [mb_strtolower($values['company'])])
                    ->whereRaw('lower(role) = ?', [mb_strtolower($values['role'])])))
            ->first();
    }
}
