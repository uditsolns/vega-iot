<?php
namespace App\Http\Requests\Reading;

use Illuminate\Foundation\Http\FormRequest;

class IngestReadingRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'ID' => 'required|string',
            'recorded_at' => 'required|date_format:Y-m-d H:i:s',
            'temperature' => 'nullable|numeric|between:-100,200',
            'humidity' => 'nullable|numeric|between:0,100',
            'temp_probe' => 'nullable|numeric|between:-100,200',
            'battery_voltage' => 'nullable|numeric',
            'battery_percentage' => 'nullable|numeric|between:0,100',
            'wifi_signal_strength' => 'nullable|integer|max:0',
            'firmware_version' => 'nullable|string',
            'raw_payload' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ID.required' => 'Device ID is required',
            'recorded_at.required' => 'Recorded timestamp is required',
            'recorded_at.date_format' => 'Timestamp must be in format: Y-m-d H:i:s',
            'temperature.between' => 'Temperature must be between -100 and 200',
            'humidity.between' => 'Humidity must be between 0 and 100',
            'battery_voltage.numeric' => 'Battery voltage must be numeric',
        ];
    }
}
//
//namespace App\Http\Requests\Reading;
//
//use Illuminate\Foundation\Http\FormRequest;
//
//class IngestReadingRequest extends FormRequest
//{
//    /**
//     * Determine if the user is authorized to make this request.
//     */
//    public function authorize(): bool
//    {
//        return true;
//    }
//
//    /**
//     * Get the validation rules that apply to the request.
//     *
//     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
//     */
//    public function rules(): array
//    {
//        return [
//            'recorded_at' => ['required', 'date', 'before_or_equal:now'],
//
//            // Sensor values
//            'temperature' => ['nullable', 'numeric', 'min:-100', 'max:200'],
//            'humidity' => ['nullable', 'numeric', 'min:0', 'max:100'],
//            'temp_probe' => ['nullable', 'numeric', 'min:-100', 'max:200'],
//
//            // Battery information
//            'battery_voltage' => ['nullable', 'numeric', 'min:0'],
//            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
//
//            // WiFi signal strength (negative values in dBm)
//            'wifi_signal_strength' => ['nullable', 'integer', 'max:0'],
//
//            // Firmware version
//            'firmware_version' => ['nullable', 'string', 'max:20'],
//        ];
//    }
//}
