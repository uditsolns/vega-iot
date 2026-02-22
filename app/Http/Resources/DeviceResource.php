<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Device */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_uid' => $this->device_uid,
            'device_code' => $this->device_code,
            'device_name' => $this->device_name,
            'firmware_version' => $this->firmware_version,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->is_active,
            'last_reading_at' => $this->last_reading_at?->toISOString(),
            'location_path' => $this->getLocationPath(),

            'device_model' => $this->whenLoaded('deviceModel', fn() => [
                'id' => $this->deviceModel->id,
                'vendor' => $this->deviceModel->vendor->value,
                'model_name' => $this->deviceModel->model_name,
                'is_configurable' => $this->deviceModel->is_configurable,
            ]),

            'company' => $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),

            'area' => $this->whenLoaded('area', fn() => $this->area ? [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'hub' => $this->area->relationLoaded('hub') ? [
                    'id' => $this->area->hub->id,
                    'name' => $this->area->hub->name,
                    'location' => $this->area->hub->relationLoaded('location') ? [
                        'id' => $this->area->hub->location->id,
                        'name' => $this->area->hub->location->name,
                    ] : null,
                ] : null,
            ] : null),

            'sensors' => $this->whenLoaded('sensors', DeviceSensorResource::collection($this->sensors)
            ),

            'current_configuration' => $this->whenLoaded('currentConfiguration', fn() =>
                $this->currentConfiguration ? new DeviceConfigurationResource($this->currentConfiguration) : null
            ),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
