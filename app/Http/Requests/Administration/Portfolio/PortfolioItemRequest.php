<?php

declare(strict_types=1);

namespace App\Http\Requests\Administration\Portfolio;

use App\DTOs\Portfolio\PortfolioFieldDTO;
use App\Enums\PortfolioList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class PortfolioItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('managePortfolio');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var PortfolioList $list */
        $list = $this->route('list');

        return array_merge(...array_map(
            fn (PortfolioFieldDTO $field): array => $field->rules(),
            $list->fields(),
        ));
    }
}
