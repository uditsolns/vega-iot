<?php

namespace App\Http\Requests\Device;

use App\Enums\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDeviceModelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vendor' => ['required', Rule::enum(Vendor::class)],
            'model_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'max_slots' => ['required', 'integer', 'min:1', 'max:32'],
            'is_configurable' => ['boolean'],
            'data_format' => ['nullable', 'array'],

            'sensor_slots' => ['required', 'array', 'min:1'],
            'sensor_slots.*.slot_number' => ['required', 'integer', 'min:1'],
            'sensor_slots.*.fixed_sensor_type_id' => ['nullable', 'integer', 'exists:sensor_types,id'],
            'sensor_slots.*.label' => ['nullable', 'string', 'max:100'],
            'sensor_slots.*.accuracy' => ['nullable', 'string', 'max:50'],
            'sensor_slots.*.resolution' => ['nullable', 'string', 'max:50'],
            'sensor_slots.*.measurement_range' => ['nullable', 'string', 'max:100'],

            'available_sensor_type_ids' => ['nullable', 'array'],
            'available_sensor_type_ids.*' => ['integer', 'exists:sensor_types,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isConfigurable = $this->boolean('is_configurable');
            $slots = $this->input('sensor_slots', []);

            foreach ($slots as $i => $slot) {
                if ($isConfigurable && !empty($slot['fixed_sensor_type_id'])) {
                    $validator->errors()->add("sensor_slots.{$i}.fixed_sensor_type_id", 'Configurable models must not have fixed sensor types on slots.');
                }

                if (!$isConfigurable && empty($slot['fixed_sensor_type_id'])) {
                    $validator->errors()->add("sensor_slots.{$i}.fixed_sensor_type_id", 'Fixed models require a sensor type for each slot.');
                }
            }

            if ($isConfigurable && empty($this->input('available_sensor_type_ids'))) {
                $validator->errors()->add('available_sensor_type_ids', 'Configurable models require at least one available sensor type.');
            }
        });
    }
}
