<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\AiProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class AiSettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'anthropicApiKey' => ['nullable', 'string', 'max:500'],
            'openRouterApiKey' => ['nullable', 'string', 'max:500'],
            'openCodeGoApiKey' => ['nullable', 'string', 'max:500'],
            'removeAnthropicApiKey' => ['boolean'],
            'removeOpenRouterApiKey' => ['boolean'],
            'removeOpenCodeGoApiKey' => ['boolean'],
            'chatModel' => ['nullable', 'string', 'max:100'],
            'fastModel' => ['nullable', 'string', 'max:100'],
            'ownerNotes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Treat unchecked checkboxes, which the form leaves out, as false.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'removeAnthropicApiKey' => $this->boolean('removeAnthropicApiKey'),
            'removeOpenRouterApiKey' => $this->boolean('removeOpenRouterApiKey'),
            'removeOpenCodeGoApiKey' => $this->boolean('removeOpenCodeGoApiKey'),
        ]);
    }
}
