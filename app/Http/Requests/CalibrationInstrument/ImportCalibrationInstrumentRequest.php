<?php

namespace App\Http\Requests\CalibrationInstrument;

use Illuminate\Foundation\Http\FormRequest;

class ImportCalibrationInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // 10 MB
        ];
    }
}
