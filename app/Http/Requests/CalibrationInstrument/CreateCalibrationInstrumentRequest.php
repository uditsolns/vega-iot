<?php

namespace App\Http\Requests\CalibrationInstrument;

use Illuminate\Foundation\Http\FormRequest;

class CreateCalibrationInstrumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'instrument_name' => ['required', 'string', 'max:255'],
            'instrument_code' => ['nullable', 'string', 'max:100', 'unique:calibration_instruments,instrument_code'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string'],
            'measurement_range' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'accuracy' => ['nullable', 'string'],
            'last_calibrated_at' => ['nullable', 'date'],
            'calibration_due_at' => ['nullable', 'date', 'after:last_calibrated_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
