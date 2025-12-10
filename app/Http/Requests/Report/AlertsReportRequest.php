<?php

namespace App\Http\Requests\Report;

class AlertsReportRequest extends GenerateReportRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'device_ids' => ['nullable', 'array'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
            'severity' => ['nullable', 'in:warning,critical'],
            'status' => ['nullable', 'in:active,acknowledged,resolved'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'device_ids.array' => 'Device IDs must be an array.',
            'device_ids.*.integer' => 'Each device ID must be an integer.',
            'device_ids.*.exists' => 'One or more selected devices do not exist.',
            'area_ids.array' => 'Area IDs must be an array.',
            'area_ids.*.integer' => 'Each area ID must be an integer.',
            'area_ids.*.exists' => 'One or more selected areas do not exist.',
            'severity.in' => 'The severity must be one of: warning, critical.',
            'status.in' => 'The status must be one of: active, acknowledged, resolved.',
        ]);
    }
}
