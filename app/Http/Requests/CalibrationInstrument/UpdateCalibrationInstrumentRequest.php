<?php

namespace App\Http\Requests\CalibrationInstrument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalibrationInstrumentRequest extends FormRequest
{
    public function rules(): array
    {
        $instrument = $this->route('calibrationInstrument');

        return [
            'instrument_name' => ['sometimes', 'required', 'string', 'max:255'],
            'instrument_code' => [
                'sometimes',
                'nullable',
                Rule::unique('calibration_instruments', 'instrument_code')->ignore($instrument),
            ],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string'],
            'measurement_range' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'accuracy' => ['nullable', 'string'],
            'last_calibrated_at' => ['nullable', 'date'],
            'calibration_due_at' => ['nullable', 'date', 'after:last_calibrated_at'],
        ];
    }
}
