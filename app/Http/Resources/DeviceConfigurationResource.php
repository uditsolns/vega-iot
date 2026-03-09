<?php

namespace App\Http\Resources;

use App\Models\DeviceConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeviceConfiguration */
class DeviceConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recording_interval' => $this->recording_interval,
            'sending_interval' => $this->sending_interval,
            'wifi_ssid' => $this->wifi_ssid,
            'wifi_mode' => $this->wifi_mode,
            'timezone_offset_minutes' => $this->timezone_offset_minutes,
            'effective_from' => $this->effective_from->toISOString(),
            'effective_to' => $this->effective_to?->toISOString(),
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'is_current' => is_null($this->effective_to),
            'updated_by' => $this->whenLoaded('updatedBy', fn() => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => "{$this->updatedBy->first_name} {$this->updatedBy->last_name}",
            ] : null),
        ];
    }
}
