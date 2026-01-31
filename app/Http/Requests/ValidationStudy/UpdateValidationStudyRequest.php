<?php

namespace App\Http\Requests\ValidationStudy;

use App\Enums\ValidationQualificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateValidationStudyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'area_type' => ['sometimes', 'string', 'max:100'],
            'area_reference' => ['sometimes', 'string', 'max:100'],
            'location' => ['sometimes', 'string'],
            'number_of_loggers' => ['sometimes', 'integer', 'min:1'],
            'cfa' => ['sometimes', 'string', 'max:50'],
            'qualification_type' => [
                'sometimes',
                Rule::in(ValidationQualificationType::values()),
            ],
            'reason' => ['nullable', 'string'],
            'temperature_range' => ['nullable', 'string'],
            'duration' => ['nullable', 'string'],
            'mapping_start_at' => ['nullable', 'date'],
            'mapping_end_at' => ['nullable', 'date', 'after_or_equal:mapping_start_at'],
            'mapping_due_at' => ['nullable', 'date', 'after:mapping_end_at'],
            'report_path' => ['nullable', 'string'],
        ];
    }
}
