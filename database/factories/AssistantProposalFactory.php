<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\AssistantProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantProposal>
 */
final class AssistantProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => null,
            'message_id' => null,
            'section' => 'profile',
            'item_id' => null,
            'summary' => 'Sharpen the headline',
            'changes' => ['headline' => 'Senior Laravel Engineer'],
            'before' => ['headline' => null],
            'status' => ProposalStatus::Pending,
        ];
    }
}
