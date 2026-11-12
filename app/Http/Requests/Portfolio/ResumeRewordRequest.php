<?php

declare(strict_types=1);

namespace App\Http\Requests\Portfolio;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResumeRewordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request. The current text and
     * the job come from the editor, so they may be unsaved.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'part' => ['required', Rule::in(['headline', 'summary', 'highlights'])],
            'experience_id' => ['required_if:part,highlights', 'nullable', 'integer', Rule::exists('experiences', 'id')],
            'current' => ['nullable', 'string', 'max:10000'],
            'job_application_id' => ['nullable', 'integer', Rule::exists('job_applications', 'id')],
        ];
    }
}
