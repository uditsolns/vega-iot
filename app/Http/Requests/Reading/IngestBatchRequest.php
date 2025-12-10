<?php

namespace App\Http\Requests\Reading;

use Illuminate\Foundation\Http\FormRequest;

class IngestBatchRequest extends FormRequest
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
            'readings' => ['required', 'array', 'min:1', 'max:100'],

            // Each reading validation
            'readings.*.recorded_at' => ['required', 'date', 'before_or_equal:now'],
            'readings.*.temperature' => ['nullable', 'numeric', 'min:-100', 'max:200'],
            'readings.*.humidity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'readings.*.temp_probe' => ['nullable', 'numeric', 'min:-100', 'max:200'],
            'readings.*.battery_voltage' => ['nullable', 'numeric', 'min:0'],
            'readings.*.battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'readings.*.wifi_signal_strength' => ['nullable', 'integer', 'max:0'],
            'readings.*.firmware_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
