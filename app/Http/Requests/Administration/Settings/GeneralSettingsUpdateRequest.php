<?php

declare(strict_types=1);

namespace App\Http\Requests\Administration\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class GeneralSettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'siteName' => ['nullable', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'supportEmail' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
