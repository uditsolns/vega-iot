<?php

namespace App\Http\Requests\Report;

class ComplianceReportRequest extends GenerateReportRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'area_ids' => ['required', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'area_ids.required' => 'At least one area ID is required.',
            'area_ids.array' => 'Area IDs must be an array.',
            'area_ids.*.integer' => 'Each area ID must be an integer.',
            'area_ids.*.exists' => 'One or more selected areas do not exist.',
        ]);
    }
}
