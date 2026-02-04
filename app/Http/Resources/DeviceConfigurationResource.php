<?php

namespace App\Http\Resources;

use App\Models\DeviceConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeviceConfiguration */
class DeviceConfigurationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,

            // Temperature thresholds (internal)
            'temp_min_critical' => $this->temp_min_critical,
            'temp_max_critical' => $this->temp_max_critical,
            'temp_min_warning' => $this->temp_min_warning,
            'temp_max_warning' => $this->temp_max_warning,

            // Humidity thresholds
            'humidity_min_critical' => $this->humidity_min_critical,
            'humidity_max_critical' => $this->humidity_max_critical,
            'humidity_min_warning' => $this->humidity_min_warning,
            'humidity_max_warning' => $this->humidity_max_warning,

            // Temperature probe thresholds
            'temp_probe_min_critical' => $this->temp_probe_min_critical,
            'temp_probe_max_critical' => $this->temp_probe_max_critical,
            'temp_probe_min_warning' => $this->temp_probe_min_warning,
            'temp_probe_max_warning' => $this->temp_probe_max_warning,

            // Recording intervals
            'record_interval' => $this->record_interval,
            'send_interval' => $this->send_interval,

            // WiFi configuration (wifi_password EXCLUDED - in $hidden array)
            'wifi_ssid' => $this->wifi_ssid,

            // Active sensor selection
            'active_temp_sensor' => $this->active_temp_sensor,

            // Tracking
            'is_current' => $this->is_current,
            'updated_by' => $this->updated_by,

            // Relationships
            'device' => new DeviceResource($this->whenLoaded('device')),
            'updated_by_user' => new UserResource($this->whenLoaded('updatedBy')),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
