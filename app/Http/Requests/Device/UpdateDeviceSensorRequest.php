<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceSensorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
