<?php

namespace App\Http\Requests\Device\Bulk;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkConfigureDevicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'device_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'device_ids.*' => [
                'required',
                'integer',
                'exists:devices,id',
            ],

            // Temperature thresholds
            'temp_min_critical' => ['sometimes', 'numeric', 'min:-100', 'max:200'],
            'temp_max_critical' => ['sometimes', 'numeric', 'min:-100', 'max:200'],
            'temp_min_warning' => ['sometimes', 'numeric', 'min:-100', 'max:200'],
            'temp_max_warning' => ['sometimes', 'numeric', 'min:-100', 'max:200'],

            // Humidity thresholds
            'humidity_min_critical' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'humidity_max_critical' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'humidity_min_warning' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'humidity_max_warning' => ['sometimes', 'numeric', 'min:0', 'max:100'],

            // Temperature probe thresholds
            'temp_probe_min_critical' => ['sometimes', 'nullable', 'numeric', 'min:-100', 'max:200'],
            'temp_probe_max_critical' => ['sometimes', 'nullable', 'numeric', 'min:-100', 'max:200'],
            'temp_probe_min_warning' => ['sometimes', 'nullable', 'numeric', 'min:-100', 'max:200'],
            'temp_probe_max_warning' => ['sometimes', 'nullable', 'numeric', 'min:-100', 'max:200'],

            // Intervals
            'record_interval' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'send_interval' => ['sometimes', 'integer', 'min:1', 'max:1440'],

            // WiFi configuration
            'wifi_ssid' => ['sometimes', 'nullable', 'string', 'max:100'],
            'wifi_password' => ['sometimes', 'nullable', 'string', 'max:100'],

            // Active sensor
            'active_temp_sensor' => ['sometimes', 'string', 'in:INT,EXT'],
        ];
    }
}
