<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
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
        $deviceId = $this->route('device')->id;

        return [
            'device_uid' => ['sometimes', 'string', 'max:50', Rule::unique('devices', 'device_uid')->ignore($deviceId)],
            'device_code' => ['sometimes', 'string', 'max:20', Rule::unique('devices', 'device_code')->ignore($deviceId)],
            'type' => ['sometimes', 'string', Rule::in(DeviceType::values())],
            'make' => ['sometimes', 'string', 'max:50'],
            'model' => ['sometimes', 'string', 'max:50'],
            'firmware_version' => ['sometimes', 'string', 'max:50'],

            // Sensor specifications
            'temp_resolution' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],
            'temp_accuracy' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],
            'humidity_resolution' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],
            'humidity_accuracy' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],
            'temp_probe_resolution' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],
            'temp_probe_accuracy' => ['sometimes', 'numeric', 'min:0', 'max:99.99'],

            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
