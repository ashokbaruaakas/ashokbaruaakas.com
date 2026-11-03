<?php

declare(strict_types=1);

namespace App\Http\Requests\Administration\Portfolio;

use App\Enums\PortfolioMediaSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

final class MediaUploadRequest extends FormRequest
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
        /** @var PortfolioMediaSlot $slot */
        $slot = $this->route('slot');

        $rules = ['required', File::types($slot->extensions())->max(10 * 1024)];

        if ($slot === PortfolioMediaSlot::ShareImage) {
            $rules[] = 'dimensions:width=1200,height=630';
        } elseif ($slot->minimumSize() > 0) {
            $rules[] = "dimensions:min_width={$slot->minimumSize()},min_height={$slot->minimumSize()}";
        }

        return ['file' => $rules];
    }

    /**
     * Get the custom messages for the validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var PortfolioMediaSlot $slot */
        $slot = $this->route('slot');

        return [
            'file.dimensions' => $slot === PortfolioMediaSlot::ShareImage
                ? 'This image must be exactly 1200 × 630 pixels.'
                : "This image must be at least {$slot->minimumSize()} × {$slot->minimumSize()} pixels.",
        ];
    }
}
