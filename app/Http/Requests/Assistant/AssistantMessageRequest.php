<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistant;

use App\Ai\PortfolioSection;
use App\Http\Requests\Portfolio\ResumeUpdateRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssistantMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request. A message sent from
     * the resume editor carries the resume, the editor's unsaved data and how its
     * preview lays it out. One sent from a portfolio form carries the section, the
     * item when it is saved, and the form's unsaved values.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'message' => ['required', 'string', 'max:20000'],
            'conversation' => ['nullable', 'string', 'uuid'],
            'job' => ['nullable', 'integer', Rule::exists('job_applications', 'id')],
            'resume' => ['nullable', 'integer', Rule::exists('resumes', 'id')->whereNull('archived_at')],
            'resume_draft' => ['required_with:resume', 'array'],
            'resume_preview' => ['nullable', 'array:pages,last_page_fill'],
            'resume_preview.pages' => ['required_with:resume_preview', 'integer', 'min:1', 'max:50'],
            'resume_preview.last_page_fill' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'portfolio_draft' => ['nullable', 'array:section,item,values'],
            'portfolio_draft.section' => ['required_with:portfolio_draft', 'string', Rule::in(PortfolioSection::keys())],
            'portfolio_draft.item' => ['nullable', 'integer'],
            'portfolio_draft.values' => ['present_with:portfolio_draft', 'array'],
        ];

        if ($this->filled('resume')) {
            foreach (ResumeUpdateRequest::fields() as $field => $fieldRules) {
                $rules["resume_draft.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }
}
