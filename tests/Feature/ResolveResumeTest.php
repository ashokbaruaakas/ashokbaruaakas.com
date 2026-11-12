<?php

declare(strict_types=1);

use App\Actions\Portfolio\ResolveResumeAction;
use App\DTOs\Portfolio\ExperienceDTO;
use App\DTOs\Portfolio\ProjectDTO;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;

beforeEach(function (): void {
    Profile::factory()->create(['tagline' => 'Engineer — Backend', 'professional_summary' => 'Builds things.']);
    $this->first = Experience::factory()->create(['role' => 'First', 'sort_order' => 0, 'highlights' => ['Shipped A']]);
    $this->second = Experience::factory()->create(['role' => 'Second', 'sort_order' => 1, 'highlights' => ['Shipped B']]);
});

function resolvedRoles(Resume $resume): array
{
    return array_map(fn (ExperienceDTO $entry): string => $entry->role, resolve(ResolveResumeAction::class)->handle($resume)->experience);
}

test('items the selection has not seen join the public resume but not a private one', function (): void {
    $public = Resume::public();
    $private = Resume::factory()->create(['selection' => ['experience' => [['id' => $this->first->id, 'included' => true]]]]);

    expect(resolvedRoles($public))->toBe(['First', 'Second'])
        ->and(resolvedRoles($private))->toBe(['First']);
});

test('the selection sets the order and leaves out excluded items', function (): void {
    $third = Experience::factory()->create(['role' => 'Third', 'sort_order' => 2]);
    Resume::public()->update(['selection' => ['experience' => [
        ['id' => $third->id, 'included' => true],
        ['id' => $this->first->id, 'included' => false],
        ['id' => $this->second->id, 'included' => true],
    ]]]);

    expect(resolvedRoles(Resume::public()))->toBe(['Third', 'Second']);
});

test('new projects join the public resume only as supporting projects', function (): void {
    Project::factory()->create(['name' => 'Flagship', 'tier' => 1]);
    Project::factory()->create(['name' => 'Supporting', 'tier' => 2]);

    $projects = resolve(ResolveResumeAction::class)->handle(Resume::public())->projects;

    expect(array_map(fn (ProjectDTO $project): string => $project->name, $projects))->toBe(['Supporting']);
});

test('overrides replace the headline, summary and an experience highlights', function (): void {
    $resume = Resume::factory()->create([
        'selection' => ['experience' => [['id' => $this->first->id, 'included' => true]]],
        'overrides' => [
            'headline' => ['text' => 'Staff Engineer — Payments', 'base_hash' => Resume::baseHash('Engineer — Backend')],
            'summary' => ['text' => 'Moves money safely.', 'base_hash' => Resume::baseHash('Builds things.')],
            'highlights' => [$this->first->id => ['items' => ['Cut fees by 30%'], 'base_hash' => Resume::baseHash(['Shipped A'])]],
        ],
    ]);

    $resolved = resolve(ResolveResumeAction::class)->handle($resume);

    expect($resolved->tagline)->toBe('Staff Engineer — Payments')
        ->and($resolved->professionalSummary)->toBe('Moves money safely.')
        ->and($resolved->experience[0]->highlights)->toBe(['Cut fees by 30%']);
});
