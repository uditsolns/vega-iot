<?php

namespace App\Http\Requests\Report;

class DeviceReadingsReportRequest extends GenerateReportRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'device_ids' => ['nullable', 'array'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
            'interval' => ['in:hourly,daily,weekly'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'device_ids.array' => 'Device IDs must be an array.',
            'device_ids.*.integer' => 'Each device ID must be an integer.',
            'device_ids.*.exists' => 'One or more selected devices do not exist.',
            'interval.in' => 'The interval must be one of: hourly, daily, weekly.',
        ]);
    }
}
