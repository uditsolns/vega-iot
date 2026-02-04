<?php

namespace App\Http\Resources;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Area */
class AreaResource extends JsonResource
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
            'hub_id' => $this->hub_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,

            // Alert channel configuration
            'alert_email_enabled' => $this->alert_email_enabled,
            'alert_sms_enabled' => $this->alert_sms_enabled,
            'alert_voice_enabled' => $this->alert_voice_enabled,
            'alert_push_enabled' => $this->alert_push_enabled,

            // Notification types enabled
            'alert_warning_enabled' => $this->alert_warning_enabled,
            'alert_critical_enabled' => $this->alert_critical_enabled,
            'alert_back_in_range_enabled' => $this->alert_back_in_range_enabled,
            'alert_device_status_enabled' => $this->alert_device_status_enabled,

            // Notification interval
            'acknowledged_alert_notification_interval' => $this->acknowledged_alert_notification_interval,

            // Relationships
            'hub' => new HubResource($this->whenLoaded('hub')),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at !== null, $this->deleted_at),
        ];
    }
}
