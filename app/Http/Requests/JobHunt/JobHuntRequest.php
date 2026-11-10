<?php

declare(strict_types=1);

namespace App\Http\Requests\JobHunt;

use App\Enums\WorkMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobHuntRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:2000'],
            'work_modes' => ['nullable', 'array'],
            'work_modes.*' => ['distinct', Rule::enum(WorkMode::class)],
            'countries' => ['nullable', 'string', 'max:255'],
            'open_to_relocation' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'started_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /**
     * The hunt form leaves unticked boxes out, which means none of them.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'work_modes' => $this->input('work_modes', []),
            'open_to_relocation' => $this->boolean('open_to_relocation'),
        ]);
    }
}
