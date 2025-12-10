<?php

namespace App\Http\Requests\Alert;

use App\Enums\AlertSensorType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAlertsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(AlertStatus::values())],
            'severity' => ['nullable', Rule::in(AlertSeverity::values())],
            'type' => ['nullable', Rule::in(AlertSensorType::values())],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
            'sort' => ['nullable', 'string'],
            'include' => ['nullable', 'string'],
        ];
    }
}
