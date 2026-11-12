<?php

declare(strict_types=1);

namespace App\Http\Requests\Portfolio;

use App\Enums\ResumeAccent;
use App\Enums\ResumeFont;
use App\Enums\ResumeList;
use App\Enums\ResumeTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResumeUpdateRequest extends FormRequest
{
    /**
     * The rules for a resume's editor data.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fields(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'template' => ['required', Rule::enum(ResumeTemplate::class)],
            'accent' => ['nullable', Rule::enum(ResumeAccent::class)],
            'font' => ['nullable', Rule::enum(ResumeFont::class)],
            'job_application_id' => ['nullable', 'integer', Rule::exists('job_applications', 'id')],
            'selection' => ['sometimes', 'array', Rule::array(ResumeList::values())],
            'selection.*' => ['array'],
            'selection.*.*' => ['array:id,included'],
            'selection.*.*.id' => ['required', 'integer'],
            'selection.*.*.included' => ['required', 'boolean'],
            'overrides' => ['sometimes', 'array:headline,summary,highlights'],
            'overrides.headline' => ['nullable', 'string', 'max:255'],
            'overrides.summary' => ['nullable', 'string', 'max:2000'],
            'overrides.highlights' => ['array'],
            'overrides.highlights.*' => ['nullable', 'array', 'max:20'],
            'overrides.highlights.*.*' => ['nullable', 'string', 'max:500'],
            'reviewed_overrides' => ['sometimes', 'array'],
            'reviewed_overrides.*' => ['string', 'regex:/^(headline|summary|highlights\.\d+)$/'],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return self::fields();
    }
}
