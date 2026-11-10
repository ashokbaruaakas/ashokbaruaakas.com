<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\JobApplicationStatus;
use App\Enums\JobChance;
use App\Enums\JobRelocation;
use App\Enums\WorkMode;
use App\Http\Requests\JobHunt\JobApplicationRequest;
use App\Models\JobApplication;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Validation\Rule;

/**
 * The job fields the assistant can propose changes to, stored as an assistant proposal for the `job` section.
 */
final class JobChange
{
    public const string SECTION = 'job';

    private const array LABELS = [
        'status' => 'Status',
        'company' => 'Company',
        'role' => 'Role',
        'chance' => 'Chance of Getting It',
        'chance_note' => 'Why This Rating',
        'link' => 'Job Post Link',
        'country' => 'Country',
        'location' => 'City',
        'work_mode' => 'Work Mode',
        'relocation' => 'Relocation Help',
        'source' => 'Found Via',
        'salary' => 'Salary',
        'contact' => 'Contact',
        'applied_on' => 'Applied On',
        'deadline' => 'Apply By',
        'next_step' => 'What Happens Next',
        'next_step_on' => 'When',
        'notes' => 'Notes',
    ];

    private const array ENUMS = [
        'status' => JobApplicationStatus::class,
        'chance' => JobChance::class,
        'work_mode' => WorkMode::class,
        'relocation' => JobRelocation::class,
    ];

    /**
     * @return list<string>
     */
    public static function fields(): array
    {
        return ['status', ...JobApplication::EDITABLE_ATTRIBUTES];
    }

    public static function label(string $field): string
    {
        return self::LABELS[$field] ?? $field;
    }

    /**
     * The validation rules for the given fields, the same ones the job form uses.
     *
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function rules(array $fields): array
    {
        $rules = [...new JobApplicationRequest()->rules(), 'status' => ['required', Rule::enum(JobApplicationStatus::class)]];

        return array_intersect_key($rules, array_flip($fields));
    }

    /**
     * The values of the given fields as stored, so they compare with proposed values and can be put back on undo.
     * A status change also keeps the applied date, which the change can fill in.
     *
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function before(JobApplication $application, array $fields): array
    {
        if (in_array('status', $fields, true) && ! in_array('applied_on', $fields, true)) {
            $fields[] = 'applied_on';
        }

        return array_combine($fields, array_map(fn (string $field): mixed => self::stored($application, $field), $fields));
    }

    public static function stored(JobApplication $application, string $field): mixed
    {
        $value = $application->getAttribute($field);

        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => $value,
        };
    }

    /**
     * A stored value as the owner reads it, with enum values shown by their label.
     */
    public static function display(string $field, mixed $value): mixed
    {
        if ($field === 'relocation' && $value === null) {
            return 'Not Mentioned';
        }

        $enum = self::ENUMS[$field] ?? null;

        if ($enum === null || ! is_string($value)) {
            return $value;
        }

        return $enum::tryFrom($value)?->label() ?? $value;
    }
}
