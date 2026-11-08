<?php

declare(strict_types=1);

namespace App\Http\Requests\JobHunt;

use App\Enums\JobHuntOutcome;
use App\Models\JobHunt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class EndJobHuntRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hunt = $this->route('jobHunt');
        $startedOn = $hunt instanceof JobHunt ? $hunt->started_on->toDateString() : 'today';

        return [
            'outcome' => ['required', Rule::enum(JobHuntOutcome::class)],
            'ended_on' => ['required', 'date_format:Y-m-d', "after_or_equal:{$startedOn}", 'before_or_equal:today'],
            'close_waiting_applications' => ['required', 'boolean'],
        ];
    }

    /**
     * Treat an unchecked checkbox, which the form leaves out, as false.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'close_waiting_applications' => $this->boolean('close_waiting_applications'),
        ]);
    }
}
