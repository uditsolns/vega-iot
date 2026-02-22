<?php

namespace App\Http\Resources;

use App\Models\DeviceModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeviceModel */
class DeviceModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor' => $this->vendor->value,
            'vendor_label' => $this->vendor->label(),
            'model_name' => $this->model_name,
            'description' => $this->description,
            'max_slots' => $this->max_slots,
            'is_configurable' => $this->is_configurable,
            'sensor_slots' => $this->whenLoaded('sensorSlots', fn() =>
                $this->sensorSlots->map(fn($slot) => [
                    'slot_number' => $slot->slot_number,
                    'label' => $slot->label,
                    'sensor_type' => $slot->sensorType ? [
                        'id' => $slot->sensorType->id,
                        'name' => $slot->sensorType->name,
                        'unit' => $slot->sensorType->unit,
                    ] : null,
                    'accuracy' => $slot->accuracy,
                    'resolution' => $slot->resolution,
                    'measurement_range' => $slot->measurement_range,
                ])
            ),
            'available_sensor_types' => $this->whenLoaded('availableSensorTypes', fn() =>
                $this->availableSensorTypes->map(fn($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'unit' => $type->unit,
                ])
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
