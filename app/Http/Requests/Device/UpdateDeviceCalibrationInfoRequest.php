<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceCalibrationInfoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'calibration_start_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'calibration_end_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
        ];
    }
}
