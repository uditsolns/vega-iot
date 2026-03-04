<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceAssetInfoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installation_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'subscription_start_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'subscription_end_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'warranty_start_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'warranty_end_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'calibration_start_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'calibration_end_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
        ];
    }
}
