<?php

namespace App\Http\Requests\CalibrationInstrument;

use Illuminate\Foundation\Http\FormRequest;

class UploadReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ];
    }
}
