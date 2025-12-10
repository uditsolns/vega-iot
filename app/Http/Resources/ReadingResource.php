<?php

namespace App\Http\Resources;

use App\Models\DeviceReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceReading
 */
class ReadingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "device_id" => $this->device_id,
            "recorded_at" => $this->recorded_at,
            "received_at" => $this->received_at,

            // Denormalized hierarchy
            "company_id" => $this->company_id,
            "location_id" => $this->location_id,
            "hub_id" => $this->hub_id,
            "area_id" => $this->area_id,

            // Sensor values (already cast to decimal:2)
            "temperature" => $this->temperature,
            "humidity" => $this->humidity,
            "temp_probe" => $this->temp_probe,

            // Battery information
            "battery_voltage" => $this->battery_voltage,
            "battery_percentage" => $this->battery_percentage,

            // WiFi signal
            "wifi_signal_strength" => $this->wifi_signal_strength,

            // Firmware version
            "firmware_version" => $this->firmware_version,

            // Raw payload (already cast to array)
            "raw_payload" => $this->when(
                $request->user()?->hasPermission("readings.view_raw"),
                $this->raw_payload,
            ),
        ];
    }
}
