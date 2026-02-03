<?php

namespace App\Http\Requests\AuditReport;

use App\Enums\AuditReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(AuditReportType::values())],
            'resource_id' => ['required', 'integer', 'min:1'],
            'from_date' => ['required', 'date', 'before_or_equal:to_date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.before_or_equal' => 'From date must be before or equal to the to date.',
            'to_date.after_or_equal' => 'To date must be after or equal to the from date.',
            'to_date.before_or_equal' => 'To date cannot be in the future.',
        ];
    }
}
