<?php

namespace App\Http\Resources;

use App\Models\DeviceSensor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeviceSensor */
class DeviceSensorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slot_number' => $this->slot_number,
            'is_enabled' => $this->is_enabled,
            'label' => $this->label,
            'accuracy' => $this->accuracy,
            'resolution' => $this->resolution,
            'measurement_range' => $this->measurement_range,
            'sensor_type' => $this->whenLoaded('sensorType', fn() => [
                'id' => $this->sensorType->id,
                'name' => $this->sensorType->name,
                'unit' => $this->sensorType->unit,
                'data_type' => $this->sensorType->data_type->value,
                'supports_threshold_config' => $this->sensorType->supports_threshold_config,
            ]),
            'current_configuration' => $this->whenLoaded('currentConfiguration', fn() =>
            $this->currentConfiguration ? new SensorConfigurationResource($this->currentConfiguration) : null
            ),
        ];
    }
}
