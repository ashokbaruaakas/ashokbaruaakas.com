<?php

declare(strict_types=1);

namespace App\Http\Requests\Administration\Portfolio;

use Illuminate\Foundation\Http\FormRequest;

final class PortfolioOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
