<?php

namespace App\Http\Requests\ValidationStudy;

use App\Enums\ValidationQualificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateValidationStudyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'area_type' => ['nullable', 'string', 'max:100'],
            'area_reference' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string'],
            'number_of_loggers' => ['nullable', 'integer', 'min:1'],
            'cfa' => ['nullable', 'string', 'max:50'],
            'qualification_type' => [
                'nullable',
                Rule::in(ValidationQualificationType::values()),
            ],
            'reason' => ['nullable', 'string'],
            'temperature_range' => ['nullable', 'string'],
            'duration' => ['nullable', 'string'],
            'mapping_start_at' => ['nullable', 'date'],
            'mapping_end_at' => ['nullable', 'date', 'after_or_equal:mapping_start_at'],
            'mapping_due_at' => ['nullable', 'date', 'after:mapping_end_at'],
            'report_path' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

