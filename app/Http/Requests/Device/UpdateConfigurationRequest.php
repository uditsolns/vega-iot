<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recording_interval' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'sending_interval' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'wifi_ssid' => ['sometimes', 'nullable', 'string', 'max:100'],
            'wifi_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'wifi_mode' => ['sometimes', 'string', 'in:WPA2,WPA3,WEP,OPEN'],
            'timezone_offset_minutes' => ['sometimes', 'integer', 'min:-840', 'max:840'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->all();
            if (isset($data['sending_interval'], $data['recording_interval'])) {
                if ($data['sending_interval'] < $data['recording_interval']) {
                    $validator->errors()->add('sending_interval', 'Sending interval must be >= recording interval.');
                }
            }
        });
    }
}
