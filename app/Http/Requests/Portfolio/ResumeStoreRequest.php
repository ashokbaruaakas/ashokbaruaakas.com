<?php

declare(strict_types=1);

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResumeStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'duplicate_of' => ['nullable', 'integer', Rule::exists('resumes', 'id')],
            'job_application_id' => ['nullable', 'integer', Rule::exists('job_applications', 'id')],
        ];
    }
}
