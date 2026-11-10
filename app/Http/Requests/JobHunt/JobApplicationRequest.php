<?php

declare(strict_types=1);

namespace App\Http\Requests\JobHunt;

use App\Enums\JobApplicationStatus;
use App\Enums\JobChance;
use App\Enums\JobRelocation;
use App\Enums\WorkMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobApplicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request. The status is only chosen when
     * adding a job; later changes go through the status endpoint so they reach the timeline.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [Rule::requiredIf($this->isMethod('POST')), Rule::prohibitedIf(! $this->isMethod('POST')), Rule::enum(JobApplicationStatus::class)],
            'company' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'chance' => ['nullable', Rule::enum(JobChance::class)],
            'chance_note' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url:http,https', 'max:2048'],
            'country' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['nullable', Rule::enum(WorkMode::class)],
            'relocation' => ['nullable', Rule::enum(JobRelocation::class)],
            'source' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'applied_on' => ['nullable', 'date_format:Y-m-d'],
            'deadline' => ['nullable', 'date_format:Y-m-d'],
            'next_step' => ['nullable', 'string', 'max:255'],
            'next_step_on' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
