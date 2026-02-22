<?php

namespace App\Http\Requests\Device;

use App\Models\DeviceModel;
use Illuminate\Foundation\Http\FormRequest;

class CreateDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'device_uid' => ['required', 'string', 'max:50', 'unique:devices,device_uid'],
            'device_code' => ['required', 'string', 'max:20', 'unique:devices,device_code'],
            'device_model_id' => ['required', 'integer', 'exists:device_models,id'],
            'firmware_version' => ['nullable', 'string', 'max:20'],

            'slot_sensors' => ['array'],
            'slot_sensors.*.slot_number' => ['required', 'integer', 'min:1'],
            'slot_sensors.*.sensor_type_id' => ['required', 'integer', 'exists:sensor_types,id'],
            'slot_sensors.*.label' => ['nullable', 'string', 'max:100'],
            'slot_sensors.*.is_enabled' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $modelId = $this->input('device_model_id');
            if (!$modelId) {
                return;
            }

            $model = DeviceModel::with(['sensorSlots', 'availableSensorTypes'])
                ->find($modelId);

            if (!$model) {
                return;
            }

            if ($model->is_configurable) {
                if (empty($this->input('slot_sensors'))) {
                    $validator->errors()->add('slot_sensors', 'Configurable models require slot_sensors to be specified.');
                    return;
                }

                $availableIds = $model->availableSensorTypes->pluck('id')->toArray();
                $slotNumbers = $model->sensorSlots->pluck('slot_number')->toArray();

                foreach ($this->input('slot_sensors', []) as $i => $slotSensor) {
                    if (!in_array($slotSensor['sensor_type_id'], $availableIds)) {
                        $validator->errors()->add("slot_sensors.{$i}.sensor_type_id", 'Sensor type is not available for this device model.');
                    }

                    if (!in_array($slotSensor['slot_number'], $slotNumbers)) {
                        $validator->errors()->add("slot_sensors.{$i}.slot_number", 'Invalid slot number for this device model.');
                    }
                }
            }
        });
    }
}
