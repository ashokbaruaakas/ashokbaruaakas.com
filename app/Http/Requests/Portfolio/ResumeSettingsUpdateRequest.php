<?php

declare(strict_types=1);

namespace App\Http\Requests\Portfolio;

use App\Enums\ResumeTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResumeSettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'template' => ['required', Rule::enum(ResumeTemplate::class)],
            'page_limit' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
