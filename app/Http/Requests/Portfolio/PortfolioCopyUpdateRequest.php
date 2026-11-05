<?php

declare(strict_types=1);

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PortfolioCopyUpdateRequest extends FormRequest
{
    public const array SECTION_KEYS = ['about', 'skills', 'work', 'case-studies', 'experience', 'education', 'testimonials', 'connect', 'open-source'];

    public const array PAGE_KEYS = ['home', 'resume', 'open-source'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', Rule::array(self::SECTION_KEYS)],
            'sections.*' => [Rule::array(['eyebrow', 'title', 'description', 'labels'])],
            'sections.*.eyebrow' => ['nullable', 'string', 'max:255'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string', 'max:1000'],
            'sections.*.labels' => ['nullable', 'array:flagship,caseStudiesLink,supporting,supportingDescription'],
            'sections.*.labels.*' => ['nullable', 'string', 'max:255'],
            'pages' => ['required', Rule::array(self::PAGE_KEYS)],
            'pages.*' => [Rule::array(['title', 'description', 'image_alt'])],
            'pages.*.title' => ['required', 'string', 'max:255'],
            'pages.*.description' => ['nullable', 'string', 'max:1000'],
            'pages.*.image_alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
