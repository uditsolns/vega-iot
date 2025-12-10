<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDeviceRequest extends FormRequest
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
            'device_uid' => ['required', 'string', 'max:50', 'unique:devices,device_uid'],
            'device_code' => ['required', 'string', 'max:20', 'unique:devices,device_code'],
            'type' => ['required', 'string', Rule::in(DeviceType::values())],
            'make' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:50'],
            'firmware_version' => ['nullable', 'string', 'max:50'],

            // Sensor specifications
            'temp_resolution' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'temp_accuracy' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'humidity_resolution' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'humidity_accuracy' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'temp_probe_resolution' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'temp_probe_accuracy' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ];
    }
}
