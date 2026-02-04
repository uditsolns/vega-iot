<?php

namespace App\Http\Requests\ScheduledReport;

use App\Enums\DeviceType;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Enums\ScheduledReportFrequency;
use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduledReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('scheduled_reports.update');
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'frequency' => ['sometimes', Rule::enum(ScheduledReportFrequency::class)],
            'timezone' => 'sometimes|in:Asia/Kolkata,UTC',
            'time' => 'sometimes|date_format:H:i',
            'recipient_emails' => 'sometimes|array|min:1|max:10',
            'recipient_emails.*' => 'required|email',
            'file_type' => ['sometimes', Rule::enum(ReportFileType::class)],
            'format' => ['sometimes', Rule::enum(ReportFormat::class)],
            'device_type' => ['sometimes', Rule::enum(DeviceType::class)],
            'data_formation' => ['sometimes', Rule::enum(ReportDataFormation::class)],
            'interval' => 'sometimes|integer|min:1',
            'device_ids' => 'sometimes|array|min:1|max:50',
            'device_ids.*' => [
                'required',
                'integer',
                'exists:devices,id',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('device_ids') && $this->has('device_type')) {
                $this->validateDeviceTypes($validator);
            }
            if ($this->has('device_type') && $this->has('data_formation')) {
                $this->validateDataFormation($validator);
            }
        });
    }

    private function validateDeviceTypes($validator)
    {
        $deviceIds = $this->input('device_ids', []);
        $expectedType = DeviceType::from($this->input('device_type'));

        $devices = Device::whereIn('id', $deviceIds)
            ->forUser($this->user())
            ->get();

        foreach ($devices as $device) {
            if ($device->type !== $expectedType) {
                $validator->errors()->add(
                    'device_ids',
                    "All devices must be of type '{$expectedType->label()}'. Device {$device->device_code} is {$device->type->label()}."
                );
                break;
            }
        }

        if ($devices->count() !== count($deviceIds)) {
            $validator->errors()->add(
                'device_ids',
                'Some devices were not found or you do not have access to them.'
            );
        }
    }

    private function validateDataFormation($validator)
    {
        $deviceType = DeviceType::from($this->input('device_type'));
        $dataFormation = ReportDataFormation::from($this->input('data_formation'));

        if (!$this->isValidDataFormationForDeviceType($deviceType, $dataFormation)) {
            $validator->errors()->add(
                'data_formation',
                "Data formation '{$dataFormation->value}' is not valid for device type '{$deviceType->value}'."
            );
        }
    }

    private function isValidDataFormationForDeviceType(
        DeviceType $deviceType,
        ReportDataFormation $dataFormation
    ): bool {
        $validFormations = match ($deviceType) {
            DeviceType::SingleTemp => [
                ReportDataFormation::SingleTemperature,
            ],
            DeviceType::SingleTempHumidity => [
                ReportDataFormation::SingleTemperature,
                ReportDataFormation::CombinedTemperatureHumidity,
                ReportDataFormation::SeparateTemperatureHumidity,
            ],
            DeviceType::DualTemp => [
                ReportDataFormation::SingleTemperature,
                ReportDataFormation::CombinedProbeTemperature,
            ],
            DeviceType::DualTempHumidity => [
                ReportDataFormation::SingleTemperature,
                ReportDataFormation::CombinedTemperatureHumidity,
                ReportDataFormation::SeparateTemperatureHumidity,
                ReportDataFormation::CombinedProbeTemperature,
                ReportDataFormation::CombinedProbeTemperatureHumidity,
            ],
        };

        return in_array($dataFormation, $validFormations);
    }
}
