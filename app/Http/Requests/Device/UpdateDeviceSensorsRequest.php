<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDeviceSensorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensors'                  => ['required', 'array', 'min:1'],
            'sensors.*.slot_number'    => ['required', 'integer', 'min:1'],
            'sensors.*.sensor_type_id' => ['required', 'integer', 'exists:sensor_types,id'],
            'sensors.*.label'          => ['nullable', 'string', 'max:100'],
            'sensors.*.is_enabled'     => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $device = $this->route('device');
                if (!$device) {
                    return;
                }

                $model = $device->deviceModel;

                if (!$model->is_configurable) {
                    $validator->errors()->add('sensors', 'Sensor assignments cannot be changed on fixed device models.');
                    return;
                }

                $availableTypeIds = $model->availableSensorTypes->pluck('id')->all();
                $validSlots       = range(1, $model->max_slots);

                foreach ($this->collect('sensors') as $index => $sensor) {
                    if (!in_array($sensor['sensor_type_id'] ?? null, $availableTypeIds)) {
                        $validator->errors()->add(
                            "sensors.{$index}.sensor_type_id",
                            'This sensor type is not available for the device model.'
                        );
                    }

                    if (!in_array($sensor['slot_number'] ?? null, $validSlots)) {
                        $validator->errors()->add(
                            "sensors.{$index}.slot_number",
                            "Slot number must be between 1 and {$model->max_slots}."
                        );
                    }
                }
            },
        ];
    }
}
