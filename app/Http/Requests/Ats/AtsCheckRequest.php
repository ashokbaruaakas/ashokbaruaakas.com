<?php

declare(strict_types=1);

namespace App\Http\Requests\Ats;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AtsCheckRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_post' => ['nullable', 'string', 'min:50', 'max:20000'],
        ];
    }
}
