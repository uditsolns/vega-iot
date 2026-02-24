<?php

namespace App\Http\Requests\Report;

use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Models\DeviceSensor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'     => ['required', 'integer', 'exists:devices,id'],
            'name'          => ['required', 'string', 'max:255'],
            'file_type'     => ['required', Rule::enum(ReportFileType::class)],
            'format'        => ['required', Rule::enum(ReportFormat::class)],

            // Sensor selection replaces data_formation
            'sensor_ids'    => ['required', 'array', 'min:1', 'max:16'],
            'sensor_ids.*'  => ['required', 'integer', 'exists:device_sensors,id'],

            'interval'      => ['required', 'integer', 'between:5,1440'],
            'from_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'to_datetime'   => ['required', 'date_format:Y-m-d H:i:s', 'after:from_datetime'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateSensorsBelongToDevice($validator);
        });
    }

    /**
     * Ensure all sensor_ids belong to the given device and are enabled.
     */
    private function validateSensorsBelongToDevice($validator): void
    {
        $deviceId  = $this->input('device_id');
        $sensorIds = $this->input('sensor_ids', []);

        if (empty($deviceId) || empty($sensorIds)) {
            return;
        }

        $validCount = DeviceSensor::query()
            ->where('device_id', $deviceId)
            ->where('is_enabled', true)
            ->whereIn('id', $sensorIds)
            ->count();

        if ($validCount !== count(array_unique($sensorIds))) {
            $validator->errors()->add(
                'sensor_ids',
                'One or more selected sensors do not belong to the specified device or are disabled.'
            );
        }
    }

    public function messages(): array
    {
        return [
            'sensor_ids.required' => 'At least one sensor must be selected.',
            'sensor_ids.min'      => 'At least one sensor must be selected.',
            'interval.between'    => 'Interval must be between 5 and 1440 minutes.',
            'to_datetime.after'   => 'End datetime must be after start datetime.',
        ];
    }
}
