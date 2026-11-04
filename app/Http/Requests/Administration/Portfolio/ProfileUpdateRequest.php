<?php

declare(strict_types=1);

namespace App\Http\Requests\Administration\Portfolio;

use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;

final class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(...array_map(
            fn (PortfolioFieldDTO $field): array => $field->rules(),
            Profile::editableFields(),
        ));
    }
}
