<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $deviceId = $this->route('device')->id;

        return [
            'device_uid' => ['sometimes', 'string', 'max:50', Rule::unique('devices', 'device_uid')->ignore($deviceId)],
            'device_code' => ['sometimes', 'string', 'max:20', Rule::unique('devices', 'device_code')->ignore($deviceId)],
            'firmware_version' => ['sometimes', 'nullable', 'string', 'max:20'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
