<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class AuthenticationSettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requireEmailVerification' => ['required', 'boolean'],
        ];
    }

    /**
     * Treat an unchecked checkbox, which the form leaves out, as false.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requireEmailVerification' => $this->boolean('requireEmailVerification'),
        ]);
    }
}
