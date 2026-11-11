<?php

declare(strict_types=1);

namespace App\Http\Requests\JobHunt;

use App\Enums\ResumePdfStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobApplicationSentResumeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'resume_build_id' => ['nullable', 'integer', Rule::exists('resume_builds', 'id')->where('status', ResumePdfStatus::Ready->value)],
        ];
    }
}
