<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Validate temperature warning thresholds within critical range
            if (isset($data['temp_min_warning']) && isset($data['temp_min_critical'])) {
                if ($data['temp_min_warning'] < $data['temp_min_critical']) {
                    $validator->errors()->add('temp_min_warning', 'Temperature min warning must be greater than or equal to min critical.');
                }
            }

            if (isset($data['temp_max_warning']) && isset($data['temp_max_critical'])) {
                if ($data['temp_max_warning'] > $data['temp_max_critical']) {
                    $validator->errors()->add('temp_max_warning', 'Temperature max warning must be less than or equal to max critical.');
                }
            }

            // Validate humidity warning thresholds within critical range
            if (isset($data['humidity_min_warning']) && isset($data['humidity_min_critical'])) {
                if ($data['humidity_min_warning'] < $data['humidity_min_critical']) {
                    $validator->errors()->add('humidity_min_warning', 'Humidity min warning must be greater than or equal to min critical.');
                }
            }

            if (isset($data['humidity_max_warning']) && isset($data['humidity_max_critical'])) {
                if ($data['humidity_max_warning'] > $data['humidity_max_critical']) {
                    $validator->errors()->add('humidity_max_warning', 'Humidity max warning must be less than or equal to max critical.');
                }
            }

            // Validate temp probe warning thresholds within critical range
            if (isset($data['temp_probe_min_warning']) && isset($data['temp_probe_min_critical'])) {
                if ($data['temp_probe_min_warning'] < $data['temp_probe_min_critical']) {
                    $validator->errors()->add('temp_probe_min_warning', 'Temp probe min warning must be greater than or equal to min critical.');
                }
            }

            if (isset($data['temp_probe_max_warning']) && isset($data['temp_probe_max_critical'])) {
                if ($data['temp_probe_max_warning'] > $data['temp_probe_max_critical']) {
                    $validator->errors()->add('temp_probe_max_warning', 'Temp probe max warning must be less than or equal to max critical.');
                }
            }

            // Validate send_interval >= record_interval
            if (isset($data['send_interval']) && isset($data['record_interval'])) {
                if ($data['send_interval'] < $data['record_interval']) {
                    $validator->errors()->add('send_interval', 'Send interval must be greater than or equal to record interval.');
                }
            }
        });
    }
}
